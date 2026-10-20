@props([
    'title',
    'eyebrow' => null,
    'intro' => null,
    // list of [label, url|null]; the current page is added automatically.
    'trail' => [],
    'photo' => null,
    'photoPosition' => 'center',
])

{{-- Banner at the top of inner pages: breadcrumbs, title, intro and an optional clinic photo. --}}
<section {{ $attributes->class('relative overflow-hidden bg-cream') }}>
    <div aria-hidden="true" class="absolute -end-32 -top-32 size-[28rem] rounded-full border border-wine/10"></div>
    <div @class(['site-container relative grid gap-10 py-12 md:py-16', 'lg:grid-cols-[1.2fr_1fr] lg:items-center lg:py-20' => $photo, 'lg:py-24' => ! $photo])>
        <div>
            <x-site.breadcrumbs :items="[[__('Home'), route('public.home')], ...$trail, [$title, null]]" />
            @if ($eyebrow)
                <p class="eyebrow mt-10">{{ $eyebrow }}</p>
            @endif
            <h1 @class(['max-w-3xl text-5xl leading-[1.02] md:text-7xl', 'mt-5' => $eyebrow, 'mt-10' => ! $eyebrow])>{{ $title }}</h1>
            @if ($intro)
                <p class="mt-6 max-w-2xl text-lg leading-relaxed text-ink-muted md:text-xl">{{ $intro }}</p>
            @endif
            {{ $slot }}
        </div>
        @if ($photo)
            <x-site.photo :name="$photo" :eager="true" sizes="(min-width: 1024px) 40vw, 100vw"
                class="aspect-[5/4] w-full rounded-t-[10rem] rounded-b-[2rem] object-cover shadow-[0_40px_80px_-40px_rgb(34_27_29/0.5)]"
                style="object-position: {{ $photoPosition }}" />
        @endif
    </div>
</section>
