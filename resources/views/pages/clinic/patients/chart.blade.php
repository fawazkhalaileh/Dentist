<?php

use App\Enums\ToothSurface;
use App\Enums\TreatmentStatus;
use App\Models\Patient;
use App\Models\TreatmentItem;
use App\Support\Audit;
use App\Support\Fdi;
use App\Support\ToothDrawing;
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
     * `marks` groups whole-tooth treatments by how they are drawn (ToothDrawing::marker()).
     *
     * @return array<int, array{surfaces: array<string, array{status: TreatmentStatus, color: string}>, whole: ?array{status: TreatmentStatus, color: string}, marks: array<string, array{status: TreatmentStatus, color: string}>, counts: array<string, int>, items: list<string>}>
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
            $teeth[$tooth] ??= ['surfaces' => [], 'whole' => null, 'marks' => [], 'counts' => [], 'items' => []];
            $entry = &$teeth[$tooth];

            if ($item->surfaces->isEmpty()) {
                $entry['whole'] = $pick($entry['whole'], $item);
                $marker = ToothDrawing::marker($item->procedure);
                $entry['marks'][$marker] = $pick($entry['marks'][$marker] ?? null, $item);
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
        $scale = $dentition === 'primary' ? 0.86 : 1.0;
        $cell = 46;
        $tw = ToothDrawing::WIDTH;
        $sh = ToothDrawing::SIDE_HEIGHT;
        $perSide = count($rows['upper']) / 2;
        $midGap = 18;
        $width = count($rows['upper']) * $cell + $midGap;
        $height = 304;
        // Vertical layout: [number, side view, top view] upper | midline | [top view, side view, number] lower.
        $layout = [
            'upper' => ['label' => 11, 'side' => 18, 'top' => 100, 'hit' => [2, 144]],
            'lower' => ['label' => 299, 'side' => 206, 'top' => 162, 'hit' => [156, 302]],
        ];
        $opacity = fn ($mark) => match ($mark['status'] ?? null) { TreatmentStatus::Completed => 1, TreatmentStatus::InProgress => 0.6, TreatmentStatus::Planned => 0.3, default => 0 };
        $dash = fn ($mark) => ($mark['status'] ?? null) === TreatmentStatus::Planned ? '3 2' : 'none';
    @endphp

    {{-- The chart is laid out as the dentist faces the patient, so it is not mirrored in right-to-left languages. --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-gradient-to-b from-white to-zinc-50 p-4 dark:border-zinc-700 dark:from-zinc-900 dark:to-zinc-900" dir="ltr">
        <svg viewBox="0 0 {{ $width }} {{ $height }}" class="mx-auto block w-full min-w-[720px] max-w-5xl" role="group" aria-label="{{ __('Dental chart, FDI numbering. Select a tooth to see its treatments.') }}">
            <defs>
                <linearGradient id="implant" x1="0" x2="1" y1="0" y2="0">
                    <stop offset="0" stop-color="#9aa1aa" /><stop offset="0.45" stop-color="#e5e7eb" /><stop offset="1" stop-color="#8b929b" />
                </linearGradient>
            </defs>

            <line x1="{{ $width / 2 }}" y1="4" x2="{{ $width / 2 }}" y2="{{ $height - 4 }}" stroke="currentColor" stroke-opacity="0.15" stroke-dasharray="3 3" />
            <line x1="0" y1="151" x2="{{ $width }}" y2="151" stroke="currentColor" stroke-opacity="0.15" stroke-dasharray="3 3" />
            <text x="2" y="148" font-size="8" fill="currentColor" fill-opacity="0.5">{{ __('R') }}</text>
            <text x="{{ $width - 8 }}" y="148" font-size="8" fill="currentColor" fill-opacity="0.5">{{ __('L') }}</text>

            @foreach (['upper', 'lower'] as $row)
                @php $l = $layout[$row]; @endphp
                @foreach ($rows[$row] as $index => $tooth)
                    @php
                        $x = $index * $cell + ($index >= $perSide ? $midGap : 0) + ($cell - $tw) / 2;
                        $summary = $this->teeth[$tooth] ?? null;
                        $sides = $this->sides($tooth);
                        $s = $summary['surfaces'] ?? [];
                        $marks = $summary['marks'] ?? [];
                        $type = ToothDrawing::type($tooth);
                        $inner = ToothDrawing::innerArea($type);
                        $regions = ToothDrawing::regions([0, 0, 40, 40], $inner);
                        [$i1, $j1, $i2, $j2] = $inner;
                        $extracted = ($marks['extraction']['status'] ?? null) === TreatmentStatus::Completed;
                        $implanted = ($marks['implant']['status'] ?? null) === TreatmentStatus::Completed;
                        // Rendered images are already oriented (upper: roots up; lower: crown up); children's teeth shrink towards the bite.
                        $anchor = $row === 'upper' ? $sh : 0;
                        $sideTransform = "translate(0 {$l['side']}) translate(20 {$anchor}) scale({$scale}) translate(-20 -{$anchor})";
                        $topTransform = "translate(0 {$l['top']}) translate(20 20) scale({$scale}) translate(-20 -20)";
                        $isSelected = $selected === $tooth;
                    @endphp
                    @if ($embedded)
                        <g role="button" tabindex="0" aria-label="{{ $this->label($tooth) }}" aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                           wire:click="selectTooth({{ $tooth }})" wire:keydown.enter="selectTooth({{ $tooth }})" wire:keydown.space.prevent="selectTooth({{ $tooth }})"
                           class="group cursor-pointer outline-none" transform="translate({{ $x }} 0)">
                    @else
                        <a href="{{ route('clinic.patients.show', ['patient' => $patient, 'tab' => 'treatments', 'tooth' => $tooth]) }}" wire:navigate aria-label="{{ $this->label($tooth) }}" class="group outline-none" transform="translate({{ $x }} 0)">
                    @endif
                        <title>{{ $this->label($tooth) }}</title>
                        {{-- Hit area: also shows hover, keyboard focus and the selected tooth. --}}
                        <rect x="-3" y="{{ $l['hit'][0] }}" width="{{ $tw + 6 }}" height="{{ $l['hit'][1] - $l['hit'][0] }}" rx="8"
                              @class([
                                  'transition-colors',
                                  'fill-accent/10 stroke-accent' => $isSelected,
                                  'fill-transparent stroke-transparent group-hover:fill-zinc-500/5 group-focus-visible:stroke-accent' => ! $isSelected,
                              ]) stroke-width="1.5" />

                        {{-- Side view: a rendered image of the real tooth (crown and roots). --}}
                        <g transform="{{ $sideTransform }}">
                            {{-- Crown and root masks are only loaded for teeth that need them. --}}
                            @if (isset($marks['crown']) || isset($marks['tint']) || isset($marks['canal']) || isset($marks['implant']))
                                <defs>
                                    <mask id="crown-{{ $tooth }}" maskUnits="userSpaceOnUse" x="0" y="0" width="{{ $tw }}" height="{{ $sh }}"><image href="{{ ToothDrawing::image($tooth, 'side-crown') }}" width="{{ $tw }}" height="{{ $sh }}" /></mask>
                                    <mask id="root-{{ $tooth }}" maskUnits="userSpaceOnUse" x="0" y="0" width="{{ $tw }}" height="{{ $sh }}"><image href="{{ ToothDrawing::image($tooth, 'side-root') }}" width="{{ $tw }}" height="{{ $sh }}" /></mask>
                                </defs>
                            @endif
                            <image href="{{ ToothDrawing::image($tooth, 'side') }}" width="{{ $tw }}" height="{{ $sh }}" @if ($extracted) opacity="0.2" @endif />
                            @if (isset($marks['implant']))
                                {{-- Implant: once placed the root becomes a threaded metal post; while planned, a dashed post outline. --}}
                                @php
                                    $post = $row === 'upper' ? [10, 52] : [26, 68];
                                    $threads = implode(' ', array_map(fn ($k) => 'M10,'.($post[0] + 4 + $k * ($post[1] - $post[0] - 6) / 7).' l20,-3', range(0, 7)));
                                @endphp
                                @if ($implanted)
                                    <g mask="url(#root-{{ $tooth }})">
                                        <rect width="{{ $tw }}" height="{{ $sh }}" fill="url(#implant)" />
                                        <path d="{{ $threads }}" stroke="#6b7280" stroke-width="0.9" stroke-opacity="0.8" />
                                        <rect width="{{ $tw }}" height="{{ $sh }}" fill="{{ $marks['implant']['color'] }}" fill-opacity="0.18" />
                                    </g>
                                @else
                                    <path d="M15.5,{{ $post[0] }} L24.5,{{ $post[0] }} L23.5,{{ $post[1] }} L16.5,{{ $post[1] }} Z"
                                          fill="{{ $marks['implant']['color'] }}" fill-opacity="{{ $opacity($marks['implant']) * 0.35 }}" stroke="{{ $marks['implant']['color'] }}" stroke-width="1.1" stroke-dasharray="{{ $dash($marks['implant']) }}" />
                                @endif
                            @endif
                            @if (isset($marks['canal']))
                                {{-- Root canal: the root is filled with the procedure colour. --}}
                                <rect width="{{ $tw }}" height="{{ $sh }}" fill="{{ $marks['canal']['color'] }}" fill-opacity="{{ max(0.35, $opacity($marks['canal']) * 0.75) }}" mask="url(#root-{{ $tooth }})" />
                            @endif
                            @if (isset($marks['tint']))
                                <rect width="{{ $tw }}" height="{{ $sh }}" fill="{{ $marks['tint']['color'] }}" fill-opacity="{{ $opacity($marks['tint']) * 0.5 }}" mask="url(#crown-{{ $tooth }})" />
                            @endif
                            @if (isset($marks['crown']))
                                <rect width="{{ $tw }}" height="{{ $sh }}" fill="{{ $marks['crown']['color'] }}" fill-opacity="{{ $opacity($marks['crown']) * 0.8 }}" mask="url(#crown-{{ $tooth }})" />
                            @endif
                        </g>
                        @php $pending = collect($marks)->first(fn ($m) => $m['status'] === TreatmentStatus::Planned); @endphp
                        @if ($pending)
                            {{-- Planned whole-tooth work: a dashed frame in the procedure colour, matching the legend. --}}
                            <rect x="0.5" y="{{ $l['side'] + 1 }}" width="{{ $tw - 1 }}" height="{{ $sh - 2 }}" rx="7" fill="none" stroke="{{ $pending['color'] }}" stroke-width="1.2" stroke-dasharray="3 2" stroke-opacity="0.9" />
                        @endif
                        @if (isset($marks['extraction']))
                            @php $cross = "M6,".($l['side'] + 10).' L34,'.($l['side'] + 68).' M34,'.($l['side'] + 10).' L6,'.($l['side'] + 68); @endphp
                            {{-- A light halo keeps the cross visible on dark backgrounds and dark procedure colours. --}}
                            <path d="{{ $cross }}" stroke="#ffffff" stroke-opacity="0.75" stroke-width="4.5" stroke-linecap="round" />
                            <path d="{{ $cross }}" stroke="{{ $marks['extraction']['color'] }}" stroke-width="2.2" stroke-linecap="round" stroke-dasharray="{{ $dash($marks['extraction']) }}" stroke-opacity="{{ max(0.6, $opacity($marks['extraction'])) }}" />
                        @endif

                        {{-- Top view: the rendered biting surface, with the five surfaces laid over its real outline. --}}
                        <g transform="{{ $topTransform }}" @if ($extracted) opacity="0.2" @endif>
                            <defs>
                                <mask id="outline-{{ $tooth }}" mask-type="alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="40" height="40"><image href="{{ ToothDrawing::image($tooth, 'top') }}" width="40" height="40" /></mask>
                            </defs>
                            <image href="{{ ToothDrawing::image($tooth, 'top') }}" width="40" height="40" />
                            <g mask="url(#outline-{{ $tooth }})">
                                @foreach ($regions as $region => $points)
                                    @php $mark = $s[$sides[$region]] ?? null; @endphp
                                    <polygon points="{{ $points }}" fill="{{ $mark['color'] ?? 'transparent' }}" fill-opacity="{{ $opacity($mark) * 0.85 }}" stroke="#ffffff" stroke-opacity="0.55" stroke-width="0.6" />
                                    @if ($mark)
                                        <polygon points="{{ $points }}" fill="none" stroke="{{ $mark['color'] }}" stroke-width="1.1" stroke-dasharray="{{ $dash($mark) }}" />
                                    @endif
                                @endforeach
                                @php $mark = $s[$sides['center']] ?? null; @endphp
                                <rect x="{{ $i1 }}" y="{{ $j1 }}" width="{{ $i2 - $i1 }}" height="{{ $j2 - $j1 }}" rx="{{ $type === 'incisor' ? 1.5 : 3 }}" fill="{{ $mark['color'] ?? 'transparent' }}" fill-opacity="{{ $opacity($mark) * 0.85 }}" stroke="{{ $mark['color'] ?? '#ffffff' }}" stroke-opacity="{{ $mark ? 1 : 0.55 }}" stroke-width="{{ $mark ? 1.1 : 0.6 }}" stroke-dasharray="{{ $dash($mark) }}" />
                                @if (isset($marks['crown']))
                                    <rect width="40" height="40" fill="{{ $marks['crown']['color'] }}" fill-opacity="{{ $opacity($marks['crown']) * 0.6 }}" />
                                @endif
                                @if (isset($marks['tint']))
                                    <rect width="40" height="40" fill="{{ $marks['tint']['color'] }}" fill-opacity="{{ $opacity($marks['tint']) * 0.35 }}" />
                                @endif
                            </g>
                        </g>
                        @if (isset($marks['extraction']))
                            <path d="M8,{{ $l['top'] + 6 }} L32,{{ $l['top'] + 34 }} M32,{{ $l['top'] + 6 }} L8,{{ $l['top'] + 34 }}" stroke="#ffffff" stroke-opacity="0.75" stroke-width="4.5" stroke-linecap="round" />
                            <path d="M8,{{ $l['top'] + 6 }} L32,{{ $l['top'] + 34 }} M32,{{ $l['top'] + 6 }} L8,{{ $l['top'] + 34 }}" stroke="{{ $marks['extraction']['color'] }}" stroke-width="2.2" stroke-linecap="round" stroke-dasharray="{{ $dash($marks['extraction']) }}" stroke-opacity="{{ max(0.6, $opacity($marks['extraction'])) }}" />
                        @endif

                        <text x="{{ $tw / 2 }}" y="{{ $l['label'] }}" text-anchor="middle" font-size="9.5"
                              @class(['font-bold' => $summary !== null || $isSelected, 'fill-accent' => $isSelected, 'fill-current' => ! $isSelected])>{{ $tooth }}</text>
                    @if ($embedded) </g> @else </a> @endif
                @endforeach
            @endforeach
        </svg>
    </div>

    <p class="mt-1 text-end text-[11px] text-zinc-500 dark:text-zinc-400">
        {{ __('Tooth images: BodyParts3D, © Life Science Integrated Database Center') }}, <a href="https://creativecommons.org/licenses/by-sa/2.1/jp/deed.en" target="_blank" rel="noopener" class="underline">CC BY-SA 2.1 JP</a>
    </p>

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
