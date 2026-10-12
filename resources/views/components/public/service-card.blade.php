@props(['service'])

@php $t = $service->translation(); @endphp

<article class="flex h-full flex-col overflow-hidden rounded-lg border border-line">
    @if ($service->featuredMedia)
        <x-ui.picture :media="$service->featuredMedia" alt="" sizes="(min-width: 768px) 33vw, 100vw" class="aspect-[3/2] w-full object-cover" />
    @endif
    <div class="flex flex-1 flex-col p-5">
        <h3 class="text-lg font-semibold">
            <a href="{{ route('public.services.show', ['slug' => $t->slug]) }}" class="hover:underline">{{ $t->title }}</a>
        </h3>
        @if ($t->summary)
            <p class="mt-2 flex-1 text-sm text-ink-muted">{{ $t->summary }}</p>
        @endif
    </div>
</article>
