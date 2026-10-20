@props([
    'title',
    'summary' => null,
    'icon' => 'tooth',
    'media' => null,
    'sample' => false,
    'related' => null,
    'bookingUrl' => null,
])

{{-- Shared layout of a treatment page; the body slot holds the treatment-specific sections. --}}
<section class="relative overflow-hidden bg-cream">
    <div aria-hidden="true" class="absolute -end-32 -top-32 size-[28rem] rounded-full border border-wine/10"></div>
    <div class="site-container relative grid gap-12 py-12 md:py-16 lg:grid-cols-[1.2fr_1fr] lg:items-center lg:py-20">
        <div>
            <x-site.breadcrumbs :items="[[__('Home'), route('public.home')], [__('Treatments'), route('public.services.index')], [$title, null]]" />
            <span class="mt-10 flex size-16 items-center justify-center rounded-full bg-ivory text-wine shadow-sm"><x-site.icon :name="$icon" class="size-8" /></span>
            <h1 class="mt-6 max-w-3xl text-5xl leading-[1.02] md:text-7xl">{{ $title }}</h1>
            @if ($summary)
                <p class="mt-6 max-w-2xl text-lg leading-relaxed text-ink-muted md:text-xl">{{ $summary }}</p>
            @endif
            <div class="mt-8 flex flex-wrap items-center gap-3">
                <a href="{{ $bookingUrl ?? route('public.booking') }}" class="btn btn-primary">{{ __('Book a Consultation') }}</a>
                @if ($sample)
                    <span class="sample-badge">{{ __('Sample treatment page, to be confirmed by the clinic') }}</span>
                @endif
            </div>
        </div>
        @if ($media)
            <x-ui.picture :media="$media" :alt="$title" eager sizes="(min-width: 1024px) 40vw, 100vw" class="aspect-[5/4] w-full rounded-t-[10rem] rounded-b-[2rem] object-cover" />
        @else
            <x-site.photo name="treatment-room" :eager="true" sizes="(min-width: 1024px) 40vw, 100vw" class="aspect-[5/4] w-full rounded-t-[10rem] rounded-b-[2rem] object-cover" />
        @endif
    </div>
</section>

<div class="site-container grid gap-14 py-20 md:py-24 lg:grid-cols-[1fr_20rem] lg:gap-20">
    <div class="min-w-0 space-y-16">
        {{ $slot }}
    </div>
    <aside class="lg:sticky lg:top-28 lg:self-start">
        <div class="rounded-[2rem] bg-wine p-8 text-ivory">
            <p class="font-display text-3xl leading-tight">{{ __('Questions about :treatment?', ['treatment' => $title]) }}</p>
            <p class="mt-3 text-sm text-ivory/80">{{ __('Every mouth is different. A consultation is the best way to find out what suits you.') }}</p>
            <a href="{{ $bookingUrl ?? route('public.booking') }}" class="btn btn-light btn-sm mt-6 w-full">{{ __('Book a Consultation') }}</a>
            <a href="{{ route('public.contact') }}" class="btn btn-ghost-light btn-sm mt-3 w-full">{{ __('Contact Us') }}</a>
        </div>
        <p class="mt-5 px-2 text-xs leading-relaxed text-stone">{{ __('This page gives general information only. Your dentist will advise on what is suitable for you after an examination.') }}</p>
    </aside>
</div>

@if ($related && $related->isNotEmpty())
    <section aria-labelledby="related-heading" class="border-t border-line py-20 md:py-24">
        <div class="site-container">
            <h2 id="related-heading" class="text-4xl">{{ __('Related treatments') }}</h2>
            <ul class="mt-10 grid gap-5 md:grid-cols-3">
                @foreach ($related as $treatment)
                    <li><x-site.treatment-card :treatment="$treatment" /></li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
