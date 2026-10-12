{{-- One medical history version. Allergies come first: they are what staff must check before treating. --}}
<dl class="grid gap-3 text-sm sm:grid-cols-[12rem_1fr]">
    <dt class="font-medium">{{ __('Allergies') }}</dt>
    <dd>
        @if ($history->allergies)
            <span class="whitespace-pre-line font-semibold text-red-700 dark:text-red-400" dir="auto">{{ $history->allergies }}</span>
        @elseif ($history->no_known_allergies)
            {{ __('No known allergies') }}
        @else
            <span class="text-amber-700 dark:text-amber-400">{{ __('Not recorded') }}</span>
        @endif
    </dd>
    <dt class="font-medium">{{ __('Current medications') }}</dt>
    <dd class="whitespace-pre-line" dir="auto">{{ $history->medications ?: '-' }}</dd>
    <dt class="font-medium">{{ __('Medical conditions') }}</dt>
    <dd class="whitespace-pre-line" dir="auto">{{ $history->conditions ?: '-' }}</dd>
    <dt class="font-medium">{{ __('Notes') }}</dt>
    <dd class="whitespace-pre-line" dir="auto">{{ $history->notes ?: '-' }}</dd>
</dl>
