{{-- One dental history version. --}}
<dl class="grid gap-3 text-sm sm:grid-cols-[12rem_1fr]">
    <dt class="font-medium">{{ __('Main complaint') }}</dt>
    <dd class="whitespace-pre-line" dir="auto">{{ $history->chief_complaint ?: '-' }}</dd>
    <dt class="font-medium">{{ __('Previous dental treatment') }}</dt>
    <dd class="whitespace-pre-line" dir="auto">{{ $history->previous_treatments ?: '-' }}</dd>
    <dt class="font-medium">{{ __('Notes') }}</dt>
    <dd class="whitespace-pre-line" dir="auto">{{ $history->notes ?: '-' }}</dd>
</dl>
