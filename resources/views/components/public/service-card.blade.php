@props(['service'])

@php $t = $service->translation(); @endphp

<article class="relative flex h-full flex-col overflow-hidden rounded-xl border border-line transition hover:border-brand hover:shadow-sm">
    @if ($service->featuredMedia)
        <x-ui.picture :media="$service->featuredMedia" alt="" sizes="(min-width: 768px) 33vw, 100vw" class="aspect-[3/2] w-full object-cover" />
    @endif
    <div class="flex flex-1 flex-col p-5">
        <h3 class="text-lg font-semibold">
            {{-- The link covers the whole card. --}}
            <a href="{{ route('public.services.show', ['slug' => $t->slug]) }}" class="after:absolute after:inset-0">{{ $t->title }}</a>
        </h3>
        @if ($t->summary)
            <p class="mt-2 flex-1 text-sm text-ink-muted">{{ $t->summary }}</p>
        @endif
        <span class="mt-4 text-sm font-medium text-brand" aria-hidden="true">{{ __('Learn more') }} <span class="inline-block rtl:rotate-180">→</span></span>
    </div>
</article>
