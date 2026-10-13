@props(['title', 'id', 'link' => null, 'linkLabel' => null])

<section aria-labelledby="{{ $id }}" {{ $attributes->class('mt-14') }}>
    <div class="mb-5 flex items-end justify-between gap-4">
        <h2 id="{{ $id }}" class="text-2xl font-semibold">{{ $title }}</h2>
        @if ($link)
            <a href="{{ $link }}" class="text-sm font-medium text-brand hover:underline">{{ $linkLabel }} <span class="inline-block rtl:rotate-180" aria-hidden="true">→</span></a>
        @endif
    </div>
    {{ $slot }}
</section>
