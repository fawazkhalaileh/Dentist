@props(['color' => 'currentColor', 'status' => \App\Enums\TreatmentStatus::Completed])
@php
    $gradient = 'implant-metal-'.\Illuminate\Support\Str::uuid();
    $planned = $status === \App\Enums\TreatmentStatus::Planned;
    $opacity = match ($status) { \App\Enums\TreatmentStatus::Planned => 0.3, \App\Enums\TreatmentStatus::InProgress => 0.65, default => 1 };
@endphp
<svg {{ $attributes->class(['implant-symbol']) }} viewBox="0 0 20 50" aria-hidden="true">
    <defs>
        <linearGradient id="{{ $gradient }}" x1="0" x2="1" y1="0" y2="0">
            <stop offset="0" stop-color="#667580" />
            <stop offset="0.38" stop-color="#edf2f5" />
            <stop offset="0.65" stop-color="#aab6bf" />
            <stop offset="1" stop-color="#56636f" />
        </linearGradient>
    </defs>
    <g stroke="{{ $color }}" stroke-width="0.9" stroke-linejoin="round" stroke-dasharray="{{ $planned ? '2 1.5' : 'none' }}">
        <path d="M6 1 H14 L15 8 H5 Z M3 9 H17 L13.5 44 Q13 49 10 49 Q7 49 6.5 44 Z" fill="url(#{{ $gradient }})" fill-opacity="{{ $opacity }}" />
        @foreach (range(0, 6) as $ridge)
            @php $y = 12 + $ridge * 4.6; $inset = $ridge * 0.42; @endphp
            <path d="M{{ 2.5 + $inset }} {{ $y + 2.4 }} L{{ 17.5 - $inset }} {{ $y }} L{{ 17.2 - $inset }} {{ $y + 2 }} L{{ 2.8 + $inset }} {{ $y + 4.4 }} Z" fill="url(#{{ $gradient }})" fill-opacity="{{ $opacity }}" />
        @endforeach
    </g>
</svg>
