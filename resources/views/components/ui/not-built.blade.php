@props(['phase'])

{{-- Shown on routed pages whose feature is scheduled for a later phase. --}}
<div {{ $attributes->class('rounded-lg border border-dashed border-line p-6 dark:border-zinc-600') }} role="status" data-test="not-built">
    <p class="font-medium">{{ __('Not built yet') }}</p>
    <p class="mt-1 text-sm text-ink-muted dark:text-zinc-400">
        {{ __('This page is planned for Phase :phase. The address and access rules are already in place.', ['phase' => $phase]) }}
    </p>
</div>
