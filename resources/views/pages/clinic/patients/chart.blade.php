<?php

use App\Enums\ToothSurface;
use App\Enums\TreatmentStatus;
use App\Models\Patient;
use App\Models\TreatmentItem;
use App\Support\Audit;
use App\Support\Fdi;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Dental chart')] class extends Component {
    public Patient $patient;

    /** Inside the patient page: no page header, and a tooth click selects it in place. */
    #[Locked]
    public bool $embedded = false;

    #[Url]
    public string $dentition = 'permanent';

    /** Tooth highlighted after a click (embedded mode). */
    #[Locked]
    public ?int $selected = null;

    public function mount(Patient $patient, bool $embedded = false, ?int $selected = null): void
    {
        $this->authorize('viewClinical', $patient);
        $this->patient = $patient;
        $this->embedded = $embedded;
        $this->selected = $selected !== null && Fdi::isValid($selected) ? $selected : null;
        if (! in_array($this->dentition, ['permanent', 'primary'], true)) {
            $this->dentition = 'permanent';
        }

        if (! $embedded) {
            Audit::record('clinical.chart_viewed', $patient);
        }
    }

    /**
     * Tooth-level treatments that are not cancelled.
     *
     * @return Collection<int, TreatmentItem>
     */
    #[Computed]
    public function items(): Collection
    {
        return $this->patient->treatmentItems()
            ->with(['procedure', 'surfaces'])
            ->whereNotNull('tooth_number')
            ->where('status', '!=', TreatmentStatus::Cancelled->value)
            ->get();
    }

    #[On('treatments-changed')]
    public function refreshChart(): void
    {
        unset($this->items, $this->teeth);
    }

    public function selectTooth(int $tooth): void
    {
        abort_unless(Fdi::isValid($tooth), 404);
        $this->selected = $this->selected === $tooth ? null : $tooth;
        $this->dispatch('tooth-selected', tooth: $this->selected);
    }

    #[On('tooth-cleared')]
    public function clearSelection(): void
    {
        $this->selected = null;
    }

    /**
     * Rows of the chart as the dentist faces the patient: the patient's right on
     * the viewer's left. Upper arch first.
     *
     * @return array{upper: list<int>, lower: list<int>}
     */
    public function rows(): array
    {
        [$ur, $ul, $ll, $lr] = $this->dentition === 'primary' ? [5, 6, 7, 8] : [1, 2, 3, 4];
        $count = $this->dentition === 'primary' ? 5 : 8;
        $side = fn (int $quadrant, bool $reverse) => array_map(fn (int $p) => $quadrant * 10 + $p, $reverse ? range($count, 1) : range(1, $count));

        return [
            'upper' => [...$side($ur, true), ...$side($ul, false)],
            'lower' => [...$side($lr, true), ...$side($ll, false)],
        ];
    }

    /**
     * Per tooth and surface, the treatment to draw: the most pressing status
     * (in progress, then planned, then completed) with its procedure's colour.
     *
     * @return array<int, array{surfaces: array<string, array{status: TreatmentStatus, color: string}>, whole: ?array{status: TreatmentStatus, color: string}, counts: array<string, int>, items: list<string>}>
     */
    #[Computed]
    public function teeth(): array
    {
        $priority = fn (TreatmentStatus $s) => match ($s) {
            TreatmentStatus::InProgress => 3,
            TreatmentStatus::Planned => 2,
            TreatmentStatus::Completed => 1,
            TreatmentStatus::Cancelled => 0,
        };
        $pick = fn (?array $current, TreatmentItem $item) => $current === null || $priority($item->status) > $priority($current['status'])
            ? ['status' => $item->status, 'color' => $item->procedure->displayColor()]
            : $current;

        $teeth = [];
        foreach ($this->items as $item) {
            $tooth = (int) $item->tooth_number;
            $teeth[$tooth] ??= ['surfaces' => [], 'whole' => null, 'counts' => [], 'items' => []];
            $entry = &$teeth[$tooth];

            if ($item->surfaces->isEmpty()) {
                $entry['whole'] = $pick($entry['whole'], $item);
            }
            foreach ($item->surfaces as $surface) {
                $entry['surfaces'][$surface->surface->value] = $pick($entry['surfaces'][$surface->surface->value] ?? null, $item);
            }
            $entry['counts'][$item->status->value] = ($entry['counts'][$item->status->value] ?? 0) + 1;
            $entry['items'][] = $item->procedure->localizedName()
                .($item->surfaces->isNotEmpty() ? ' ('.$item->surfaceCodes().')' : '')
                .' · '.$item->status->label();
            unset($entry);
        }

        return $teeth;
    }

    /**
     * Procedures drawn on the chart, for the colour legend.
     *
     * @return array<string, string> name => colour
     */
    #[Computed]
    public function legend(): array
    {
        $legend = [];
        foreach ($this->items as $item) {
            $legend[$item->procedure->localizedName()] = $item->procedure->displayColor();
        }
        ksort($legend);

        return $legend;
    }

    /**
     * Which surface each side of the tooth diagram shows. Buccal faces outward
     * (up for the upper arch, down for the lower); mesial faces the midline.
     *
     * @return array{top: string, bottom: string, left: string, right: string, center: string}
     */
    public function sides(int $tooth): array
    {
        $upper = Fdi::arch($tooth) === 'upper';
        $patientRight = in_array(Fdi::quadrant($tooth), [1, 4, 5, 8], true);

        return [
            'top' => $upper ? ToothSurface::Buccal->value : ToothSurface::Lingual->value,
            'bottom' => $upper ? ToothSurface::Lingual->value : ToothSurface::Buccal->value,
            'left' => $patientRight ? ToothSurface::Distal->value : ToothSurface::Mesial->value,
            'right' => $patientRight ? ToothSurface::Mesial->value : ToothSurface::Distal->value,
            'center' => Fdi::isAnterior($tooth) ? ToothSurface::Incisal->value : ToothSurface::Occlusal->value,
        ];
    }

    public function label(int $tooth): string
    {
        $summary = $this->teeth[$tooth] ?? null;
        $parts = [__('Tooth :number', ['number' => $tooth]), __(Fdi::name($tooth))];
        if ($summary === null) {
            $parts[] = __('no treatments');
        } else {
            $parts = [...$parts, ...$summary['items']];
        }

        return implode(', ', $parts);
    }
}; ?>

<section @class(['w-full', 'max-w-6xl' => ! $embedded])>
    @unless ($embedded)
        <x-ui.page-header :title="__('Dental chart')" :description="$patient->full_name.' · '.$patient->patient_number">
            <x-slot:actions>
                <flux:button :href="route('clinic.patients.show', ['patient' => $patient, 'tab' => 'treatments'])" wire:navigate>{{ __('Open patient record') }}</flux:button>
            </x-slot:actions>
        </x-ui.page-header>
    @endunless

    <div class="mb-3 flex flex-wrap items-end justify-between gap-4">
        <flux:radio.group wire:model.live="dentition" variant="segmented" :label="__('Teeth')" class="max-w-xs">
            <flux:radio value="permanent" :label="__('Permanent')" />
            <flux:radio value="primary" :label="__('Primary (children)')" />
        </flux:radio.group>

        {{-- How status is drawn: the procedure colour, lighter while planned. --}}
        <ul class="flex flex-wrap gap-4 text-xs" aria-label="{{ __('Status') }}">
            <li class="flex items-center gap-1.5"><span aria-hidden="true" class="inline-block size-3 rounded-sm border border-dashed border-zinc-500 bg-zinc-500/25"></span>{{ __('Planned') }}</li>
            <li class="flex items-center gap-1.5"><span aria-hidden="true" class="inline-block size-3 rounded-sm bg-zinc-500/60"></span>{{ __('In progress') }}</li>
            <li class="flex items-center gap-1.5"><span aria-hidden="true" class="inline-block size-3 rounded-sm bg-zinc-500"></span>{{ __('Completed') }}</li>
        </ul>
    </div>

    @php
        $rows = $this->rows();
        $cell = 44;
        $size = 36;
        $perSide = count($rows['upper']) / 2;
        $midGap = 16;
        $width = count($rows['upper']) * $cell + $midGap;
        $opacity = fn ($mark) => match ($mark['status'] ?? null) { \App\Enums\TreatmentStatus::Completed => 1, \App\Enums\TreatmentStatus::InProgress => 0.6, \App\Enums\TreatmentStatus::Planned => 0.25, default => 0 };
        $fill = fn ($mark) => $mark ? $mark['color'] : 'transparent';
        $dash = fn ($mark) => ($mark['status'] ?? null) === \App\Enums\TreatmentStatus::Planned ? '3 2' : 'none';
    @endphp

    {{-- The chart is laid out as the dentist faces the patient, so it is not mirrored in right-to-left languages. --}}
    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900" dir="ltr">
        <svg viewBox="0 0 {{ $width }} 150" class="mx-auto block w-full min-w-[640px] max-w-4xl" role="group" aria-label="{{ __('Dental chart, FDI numbering. Select a tooth to see its treatments.') }}">
            <line x1="{{ $width / 2 }}" y1="0" x2="{{ $width / 2 }}" y2="150" stroke="currentColor" stroke-opacity="0.2" stroke-dasharray="3 3" />
            <line x1="0" y1="75" x2="{{ $width }}" y2="75" stroke="currentColor" stroke-opacity="0.2" stroke-dasharray="3 3" />
            <text x="2" y="72" font-size="8" fill="currentColor" fill-opacity="0.5">{{ __('R') }}</text>
            <text x="{{ $width - 8 }}" y="72" font-size="8" fill="currentColor" fill-opacity="0.5">{{ __('L') }}</text>

            @foreach (['upper' => 14, 'lower' => 92] as $row => $y)
                @foreach ($rows[$row] as $index => $tooth)
                    @php
                        $x = $index * $cell + ($index >= $perSide ? $midGap : 0) + ($cell - $size) / 2;
                        $summary = $this->teeth[$tooth] ?? null;
                        $sides = $this->sides($tooth);
                        $s = $summary['surfaces'] ?? [];
                        $whole = $summary['whole'] ?? null;
                        $c = 12;
                        $o = ($size - $c) / 2;
                        $labelY = $row === 'upper' ? $y - 4 : $y + $size + 10;
                        $regions = [
                            'top' => "{$x},{$y} ".($x + $size).",{$y} ".($x + $o + $c).','.($y + $o).' '.($x + $o).','.($y + $o),
                            'bottom' => "{$x},".($y + $size).' '.($x + $size).','.($y + $size).' '.($x + $o + $c).','.($y + $o + $c).' '.($x + $o).','.($y + $o + $c),
                            'left' => "{$x},{$y} ".($x + $o).','.($y + $o).' '.($x + $o).','.($y + $o + $c)." {$x},".($y + $size),
                            'right' => ($x + $size).",{$y} ".($x + $o + $c).','.($y + $o).' '.($x + $o + $c).','.($y + $o + $c).' '.($x + $size).','.($y + $size),
                        ];
                    @endphp
                    @if ($embedded)
                        <g role="button" tabindex="0" aria-label="{{ $this->label($tooth) }}" aria-pressed="{{ $selected === $tooth ? 'true' : 'false' }}"
                           wire:click="selectTooth({{ $tooth }})" wire:keydown.enter="selectTooth({{ $tooth }})" wire:keydown.space.prevent="selectTooth({{ $tooth }})"
                           class="cursor-pointer outline-none focus-visible:[&>rect.frame]:stroke-[3]">
                    @else
                        <a href="{{ route('clinic.patients.show', ['patient' => $patient, 'tab' => 'treatments', 'tooth' => $tooth]) }}" wire:navigate aria-label="{{ $this->label($tooth) }}" class="outline-none focus-visible:[&>rect.frame]:stroke-[3]">
                    @endif
                        <title>{{ $this->label($tooth) }}</title>
                        @if ($selected === $tooth)
                            <rect x="{{ $x - 3 }}" y="{{ $y - 3 }}" width="{{ $size + 6 }}" height="{{ $size + 6 }}" rx="6" fill="none" stroke="currentColor" stroke-width="2" />
                        @endif
                        {{-- Whole-tooth treatments (crown, root canal, extraction...) tint and outline the tooth. --}}
                        <rect class="frame" x="{{ $x }}" y="{{ $y }}" width="{{ $size }}" height="{{ $size }}" rx="4"
                              fill="{{ $fill($whole) }}" fill-opacity="{{ $opacity($whole) * 0.5 }}"
                              stroke="{{ $whole ? $whole['color'] : 'currentColor' }}" stroke-opacity="{{ $whole ? 1 : 0.45 }}" stroke-width="{{ $whole ? 2.5 : 1 }}" stroke-dasharray="{{ $dash($whole) }}" />
                        @foreach ($regions as $side => $points)
                            @php $mark = $s[$sides[$side]] ?? null; @endphp
                            <polygon points="{{ $points }}" fill="{{ $fill($mark) }}" fill-opacity="{{ $opacity($mark) }}" stroke="{{ $mark ? $mark['color'] : 'currentColor' }}" stroke-opacity="{{ $mark ? 0.9 : 0.25 }}" stroke-dasharray="{{ $dash($mark) }}" />
                        @endforeach
                        @php $mark = $s[$sides['center']] ?? null; @endphp
                        <rect x="{{ $x + $o }}" y="{{ $y + $o }}" width="{{ $c }}" height="{{ $c }}" fill="{{ $fill($mark) }}" fill-opacity="{{ $opacity($mark) }}" stroke="{{ $mark ? $mark['color'] : 'currentColor' }}" stroke-opacity="{{ $mark ? 0.9 : 0.25 }}" stroke-dasharray="{{ $dash($mark) }}" />
                        <text x="{{ $x + $size / 2 }}" y="{{ $labelY }}" text-anchor="middle" font-size="9" fill="currentColor" @class(['font-bold' => $summary !== null])>{{ $tooth }}</text>
                    @if ($embedded) </g> @else </a> @endif
                @endforeach
            @endforeach
        </svg>
    </div>

    @if ($this->legend !== [])
        <ul class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs" aria-label="{{ __('Procedure colours') }}">
            @foreach ($this->legend as $name => $color)
                <li class="flex items-center gap-1.5"><span aria-hidden="true" class="inline-block size-3 rounded-sm" style="background-color: {{ $color }}"></span>{{ $name }}</li>
            @endforeach
        </ul>
    @endif

    {{-- Text alternative: the same information without relying on colour or the drawing. --}}
    @unless ($embedded)
        <flux:card class="mt-6">
            <flux:heading size="lg" class="mb-3">{{ __('Teeth with treatments') }}</flux:heading>
            @php $treated = collect($this->teeth)->filter(fn ($t, $n) => in_array($n, [...$rows['upper'], ...$rows['lower']], true))->sortKeys(); @endphp
            @if ($treated->isEmpty())
                <flux:text class="text-sm">{{ __('No tooth treatments recorded for these teeth.') }}</flux:text>
            @else
                <ul class="divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                    @foreach ($treated as $number => $tooth)
                        <li class="flex flex-wrap gap-x-4 py-2">
                            <a href="{{ route('clinic.patients.show', ['patient' => $patient, 'tab' => 'treatments', 'tooth' => $number]) }}" wire:navigate class="w-56 font-medium underline">{{ $number }} · {{ __(\App\Support\Fdi::name($number)) }}</a>
                            <span>{{ implode('; ', $tooth['items']) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </flux:card>
    @endunless
</section>
