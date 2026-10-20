@props(['post'])

<article class="group relative flex h-full flex-col">
    <div class="relative overflow-hidden rounded-3xl">
        @if ($post['media'])
            <x-ui.picture :media="$post['media']" alt="" sizes="(min-width: 1024px) 33vw, 100vw" class="aspect-[4/3] w-full object-cover transition duration-700 group-hover:scale-[1.03]" />
        @elseif ($post['photo'])
            <x-site.photo :name="$post['photo']" alt="" sizes="(min-width: 1024px) 33vw, 100vw" class="aspect-[4/3] w-full object-cover transition duration-700 group-hover:scale-[1.03]" />
        @endif
        @if ($post['sample'])
            <span class="sample-badge absolute start-4 top-4">{{ __('Sample article') }}</span>
        @endif
    </div>
    <div class="flex flex-1 flex-col pt-6">
        <p class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-medium uppercase tracking-[0.14em] text-stone">
            @if ($post['category'])
                <span class="text-wine">{{ $post['category'] }}</span>
            @endif
            @if ($post['date'])
                <time datetime="{{ $post['date']->format('Y-m-d') }}">{{ $post['date']->translatedFormat('j F Y') }}@if ($post['sample']) · {{ __('demo date') }}@endif</time>
            @endif
        </p>
        <h3 class="mt-3 text-2xl leading-snug">
            <a href="{{ $post['url'] }}" class="after:absolute after:inset-0">{{ $post['title'] }}</a>
        </h3>
        @if ($post['excerpt'])
            <p class="mt-3 line-clamp-3 text-[0.9375rem] leading-relaxed text-ink-muted">{{ $post['excerpt'] }}</p>
        @endif
        <span class="link-arrow mt-5 text-sm" aria-hidden="true">{{ __('Read article') }} <x-site.icon name="arrow" class="arrow size-4" /></span>
    </div>
</article>
