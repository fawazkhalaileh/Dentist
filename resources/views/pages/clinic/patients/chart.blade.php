<?php

use App\Enums\ToothSurface;
use App\Enums\TreatmentStatus;
use App\Models\Patient;
use App\Models\TreatmentItem;
use App\Support\Audit;
use App\Support\Fdi;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Dental chart')] class extends Component {
    public Patient $patient;

    #[Url]
    public string $dentition = 'permanent';

    public function mount(Patient $patient): void
    {
        $this->authorize('viewClinical', $patient);
        $this->patient = $patient;
        if (! in_array($this->dentition, ['permanent', 'primary'], true)) {
            $this->dentition = 'permanent';
        }

        Audit::record('clinical.chart_viewed', $patient);
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
     * Per tooth: the most pressing status per surface and for the whole tooth,
     * plus counts for the text alternative.
     *
     * @return array<int, array{surfaces: array<string, TreatmentStatus>, whole: ?TreatmentStatus, counts: array<string, int>, items: list<string>}>
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
        $pick = fn (?TreatmentStatus $current, TreatmentStatus $new) => $current === null || $priority($new) > $priority($current) ? $new : $current;

        $teeth = [];
        foreach ($this->items as $item) {
            $tooth = (int) $item->tooth_number;
            $teeth[$tooth] ??= ['surfaces' => [], 'whole' => null, 'counts' => [], 'items' => []];
            $entry = &$teeth[$tooth];

            if ($item->surfaces->isEmpty()) {
                $entry['whole'] = $pick($entry['whole'], $item->status);
            }
            foreach ($item->surfaces as $surface) {
                $entry['surfaces'][$surface->surface->value] = $pick($entry['surfaces'][$surface->surface->value] ?? null, $item->status);
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
            foreach ($summary['counts'] as $status => $count) {
                $parts[] = $count.' '.strtolower(TreatmentStatus::from($status)->label());
            }
        }

        return implode(', ', $parts);
    }
}; ?>

<section class="w-full max-w-6xl">
    <x-ui.page-header :title="__('Dental chart')" :description="$patient->full_name.' · '.$patient->patient_number">
        <x-slot:actions>
            <flux:button :href="route('clinic.patients.treatment-plans', $patient)" wire:navigate>{{ __('Treatment plans') }}</flux:button>
            <flux:button :href="route('clinic.patients.show', $patient)" wire:navigate>{{ __('Back to patient') }}</flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
        <flux:radio.group wire:model.live="dentition" variant="segmented" :label="__('Teeth')" class="max-w-xs">
            <flux:radio value="permanent" :label="__('Permanent')" />
            <flux:radio value="primary" :label="__('Primary (children)')" />
        </flux:radio.group>

        <ul class="flex flex-wrap gap-4 text-sm" aria-label="{{ __('Legend') }}">
            @foreach ([\App\Enums\TreatmentStatus::Planned, \App\Enums\TreatmentStatus::InProgress, \App\Enums\TreatmentStatus::Completed] as $legend)
                <li class="flex items-center gap-2">
                    <span aria-hidden="true" class="inline-block size-3 rounded-sm" style="background-color: var(--status-{{ $legend->colorToken() }})"></span>
                    {{ $legend->label() }}
                </li>
            @endforeach
        </ul>
    </div>

    @php
        $rows = $this->rows();
        $cell = 44;
        $size = 36;
        $perSide = count($rows['upper']) / 2;
        $midGap = 16;
        $width = count($rows['upper']) * $cell + $midGap;
        $fill = fn ($status) => $status ? 'var(--status-'.$status->colorToken().')' : 'transparent';
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
                        $c = 12; // inner square
                        $o = ($size - $c) / 2;
                        $labelY = $row === 'upper' ? $y - 4 : $y + $size + 10;
                    @endphp
                    <a href="{{ route('clinic.patients.treatment-plans', ['patient' => $patient, 'tooth' => $tooth]) }}" wire:navigate aria-label="{{ $this->label($tooth) }}" class="tooth outline-none focus-visible:[&>rect.frame]:stroke-[3]">
                        <title>{{ $this->label($tooth) }}</title>
                        {{-- Whole-tooth treatments (crown, root canal, extraction...) tint the tooth. --}}
                        <rect class="frame" x="{{ $x }}" y="{{ $y }}" width="{{ $size }}" height="{{ $size }}" rx="4"
                              fill="{{ $fill($summary['whole'] ?? null) }}" fill-opacity="0.25"
                              stroke="{{ ($summary['whole'] ?? null) ? $fill($summary['whole']) : 'currentColor' }}" stroke-opacity="{{ ($summary['whole'] ?? null) ? 1 : 0.45 }}" stroke-width="{{ ($summary['whole'] ?? null) ? 2.5 : 1 }}" />
                        <polygon points="{{ $x }},{{ $y }} {{ $x + $size }},{{ $y }} {{ $x + $o + $c }},{{ $y + $o }} {{ $x + $o }},{{ $y + $o }}" fill="{{ $fill($s[$sides['top']] ?? null) }}" stroke="currentColor" stroke-opacity="0.25" />
                        <polygon points="{{ $x }},{{ $y + $size }} {{ $x + $size }},{{ $y + $size }} {{ $x + $o + $c }},{{ $y + $o + $c }} {{ $x + $o }},{{ $y + $o + $c }}" fill="{{ $fill($s[$sides['bottom']] ?? null) }}" stroke="currentColor" stroke-opacity="0.25" />
                        <polygon points="{{ $x }},{{ $y }} {{ $x + $o }},{{ $y + $o }} {{ $x + $o }},{{ $y + $o + $c }} {{ $x }},{{ $y + $size }}" fill="{{ $fill($s[$sides['left']] ?? null) }}" stroke="currentColor" stroke-opacity="0.25" />
                        <polygon points="{{ $x + $size }},{{ $y }} {{ $x + $o + $c }},{{ $y + $o }} {{ $x + $o + $c }},{{ $y + $o + $c }} {{ $x + $size }},{{ $y + $size }}" fill="{{ $fill($s[$sides['right']] ?? null) }}" stroke="currentColor" stroke-opacity="0.25" />
                        <rect x="{{ $x + $o }}" y="{{ $y + $o }}" width="{{ $c }}" height="{{ $c }}" fill="{{ $fill($s[$sides['center']] ?? null) }}" stroke="currentColor" stroke-opacity="0.25" />
                        <text x="{{ $x + $size / 2 }}" y="{{ $labelY }}" text-anchor="middle" font-size="9" fill="currentColor" @class(['font-bold' => $summary !== null])>{{ $tooth }}</text>
                    </a>
                @endforeach
            @endforeach
        </svg>
    </div>

    {{-- Text alternative: the same information without relying on colour or the drawing. --}}
    <flux:card class="mt-6">
        <flux:heading size="lg" class="mb-3">{{ __('Teeth with treatments') }}</flux:heading>
        @php $treated = collect($this->teeth)->filter(fn ($t, $n) => in_array($n, [...$rows['upper'], ...$rows['lower']], true))->sortKeys(); @endphp
        @if ($treated->isEmpty())
            <flux:text class="text-sm">{{ __('No tooth treatments recorded for these teeth.') }}</flux:text>
        @else
            <ul class="divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                @foreach ($treated as $number => $tooth)
                    <li class="flex flex-wrap gap-x-4 py-2">
                        <a href="{{ route('clinic.patients.treatment-plans', ['patient' => $patient, 'tooth' => $number]) }}" wire:navigate class="w-56 font-medium underline">{{ $number }} · {{ __(\App\Support\Fdi::name($number)) }}</a>
                        <span>{{ implode('; ', $tooth['items']) }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </flux:card>
</section>
