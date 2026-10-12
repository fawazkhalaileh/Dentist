@props(['title', 'description' => null])

<div {{ $attributes->class('mb-6 flex flex-wrap items-start justify-between gap-4') }}>
    <div>
        <h1 class="text-2xl font-semibold">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1 text-sm text-ink-muted dark:text-zinc-400">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap gap-2">{{ $actions }}</div>
    @endisset
</div>
