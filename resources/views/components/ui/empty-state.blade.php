@props(['title', 'description' => null])

<div {{ $attributes->class('rounded-lg border border-line p-8 text-center dark:border-zinc-700') }}>
    <p class="font-medium">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 text-sm text-ink-muted dark:text-zinc-400">{{ $description }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
