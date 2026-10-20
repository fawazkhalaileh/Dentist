@props([
    'title',
    'excerpt' => null,
    'category' => null,
    'categoryUrl' => null,
    'date' => null,
    'author' => null,
    'minutes' => null,
    'media' => null,
    'photo' => null,
    'sample' => false,
    'related' => null,
])

@php
    $shareUrl = urlencode(url()->current());
    $shareText = urlencode($title);
@endphp

<article>
    <header class="bg-cream">
        <div class="site-container max-w-4xl pt-12 pb-16 text-center md:pt-16">
            <x-site.breadcrumbs class="flex justify-center" :items="array_values(array_filter([[__('Home'), route('public.home')], [__('Blog'), route('public.blog.index')], $category ? [$category, $categoryUrl] : null, [$title, null]]))" />
            @if ($category)
                <p class="eyebrow mt-10 justify-center">{{ $category }}</p>
            @endif
            <h1 class="mx-auto mt-5 max-w-3xl text-5xl leading-[1.05] md:text-6xl">{{ $title }}</h1>
            @if ($excerpt)
                <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-ink-muted">{{ $excerpt }}</p>
            @endif
            <p class="mt-8 flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-sm text-stone">
                @if ($author)<span>{{ $author }}</span>@endif
                @if ($date)<time datetime="{{ $date->format('Y-m-d') }}">{{ $date->translatedFormat('j F Y') }}@if ($sample) · {{ __('demo date') }}@endif</time>@endif
                @if ($minutes)<span>{{ trans_choice(':count minute read|:count minutes read', $minutes) }}</span>@endif
                @if ($sample)<span class="sample-badge">{{ __('Sample article') }}</span>@endif
            </p>
        </div>
    </header>

    <div class="site-container max-w-5xl">
        <div class="-mt-2 overflow-hidden rounded-[2rem]">
            @if ($media)
                <x-ui.picture :media="$media" :alt="$title" eager sizes="(min-width: 1024px) 1024px, 100vw" class="aspect-[16/8] w-full object-cover" />
            @elseif ($photo)
                <x-site.photo :name="$photo" alt="" :eager="true" sizes="(min-width: 1024px) 1024px, 100vw" class="aspect-[16/8] w-full object-cover" />
            @endif
        </div>
    </div>

    <div class="site-container grid max-w-5xl gap-10 py-14 md:grid-cols-[3.5rem_1fr] md:py-20">
        <aside aria-label="{{ __('Share this article') }}" class="order-2 md:order-1">
            <ul class="flex gap-3 md:sticky md:top-28 md:flex-col">
                <li><a href="https://wa.me/?text={{ $shareText }}%20{{ $shareUrl }}" rel="noopener" target="_blank" class="flex size-11 items-center justify-center rounded-full border border-line text-wine hover:border-wine"><x-site.icon name="whatsapp" class="size-5" /><span class="sr-only">{{ __('Share on WhatsApp') }} ({{ __('opens in a new tab') }})</span></a></li>
                <li><a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" rel="noopener" target="_blank" class="flex size-11 items-center justify-center rounded-full border border-line text-wine hover:border-wine"><x-site.icon name="facebook" class="size-5" /><span class="sr-only">{{ __('Share on Facebook') }} ({{ __('opens in a new tab') }})</span></a></li>
                <li><a href="mailto:?subject={{ rawurlencode($title) }}&body={{ $shareUrl }}" class="flex size-11 items-center justify-center rounded-full border border-line text-wine hover:border-wine"><x-site.icon name="mail" class="size-5" /><span class="sr-only">{{ __('Share by email') }}</span></a></li>
            </ul>
        </aside>
        <div class="order-1 min-w-0 max-w-3xl md:order-2">
            {{ $slot }}
            <p class="mt-14 rounded-2xl border border-line p-6 text-sm leading-relaxed text-ink-muted">
                {{ __('This article is general information and does not replace advice from your dentist. If you have pain, swelling or concerns about your teeth, please get in touch.') }}
            </p>
        </div>
    </div>
</article>

@if ($related && $related->isNotEmpty())
    <section aria-labelledby="related-articles" class="border-t border-line py-20 md:py-24">
        <div class="site-container">
            <h2 id="related-articles" class="text-4xl">{{ __('Keep reading') }}</h2>
            <ul class="mt-10 grid gap-x-8 gap-y-14 md:grid-cols-3">
                @foreach ($related as $card)
                    <li><x-site.post-card :post="$card" /></li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
