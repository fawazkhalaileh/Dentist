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
    <div @class(['site-container relative grid gap-8 py-8 md:py-12', 'lg:grid-cols-[1.2fr_1fr] lg:items-center lg:py-14' => $photo, 'lg:py-16' => ! $photo])>
        <div>
            <x-site.breadcrumbs :items="[[__('Home'), route('public.home')], ...$trail, [$title, null]]" />
            @if ($eyebrow)
                <p class="eyebrow mt-7">{{ $eyebrow }}</p>
            @endif
            <h1 @class(['max-w-3xl text-5xl leading-[1.02] md:text-7xl', 'mt-4' => $eyebrow, 'mt-7' => ! $eyebrow])>{{ $title }}</h1>
            @if ($intro)
                <p class="mt-4 max-w-2xl text-lg leading-relaxed text-ink-muted md:text-xl">{{ $intro }}</p>
            @endif
            {{ $slot }}
        </div>
        @if ($photo)
            <x-site.photo :name="$photo" :eager="true" sizes="(min-width: 1024px) 40vw, 100vw"
                class="hidden aspect-[16/10] w-full rounded-t-[10rem] rounded-b-[2rem] object-cover shadow-[0_40px_80px_-40px_rgb(34_27_29/0.5)] lg:block"
                style="object-position: {{ $photoPosition }}" />
        @endif
    </div>
</section>
