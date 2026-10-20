@props(['post', 'row' => false])

{{-- row: on phones, a compact list item with a small picture beside the title. --}}

<article @class(['group relative h-full', 'flex flex-col' => ! $row, 'grid grid-cols-[6.5rem_1fr] gap-4 md:flex md:flex-col md:gap-0' => $row])>
    <div @class(['relative overflow-hidden', 'rounded-3xl' => ! $row, 'self-start rounded-2xl md:rounded-3xl' => $row])>
        @if ($post['media'])
            <x-ui.picture :media="$post['media']" alt="" sizes="(min-width: 1024px) 33vw, 100vw" @class(['w-full object-cover transition duration-700 group-hover:scale-[1.03]', 'aspect-[4/3]' => ! $row, 'aspect-square md:aspect-[4/3]' => $row]) />
        @elseif ($post['photo'])
            <x-site.photo :name="$post['photo']" alt="" sizes="(min-width: 1024px) 33vw, 100vw" @class(['w-full object-cover transition duration-700 group-hover:scale-[1.03]', 'aspect-[4/3]' => ! $row, 'aspect-square md:aspect-[4/3]' => $row]) />
        @endif
        @if ($post['sample'])
            <span @class(['sample-badge absolute start-4 top-4', 'hidden md:inline-flex' => $row])>{{ __('Sample article') }}</span>
        @endif
    </div>
    <div @class(['flex flex-1 flex-col', 'pt-6' => ! $row, 'md:pt-6' => $row])>
        <p class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-medium uppercase tracking-[0.14em] text-stone">
            @if ($post['category'])
                <span class="text-wine">{{ $post['category'] }}</span>
            @endif
            @if ($post['date'])
                <time datetime="{{ $post['date']->format('Y-m-d') }}">{{ $post['date']->translatedFormat('j F Y') }}@if ($post['sample']) · {{ __('demo date') }}@endif</time>
            @endif
        </p>
        <h3 @class(['leading-snug', 'mt-3 text-2xl' => ! $row, 'mt-1 text-lg md:mt-3 md:text-2xl' => $row])>
            <a href="{{ $post['url'] }}" class="after:absolute after:inset-0">{{ $post['title'] }}</a>
        </h3>
        @if ($post['excerpt'])
            <p @class(['mt-3 line-clamp-3 text-[0.9375rem] leading-relaxed text-ink-muted', 'hidden md:block' => $row])>{{ $post['excerpt'] }}</p>
        @endif
        <span @class(['link-arrow mt-5 text-sm', 'hidden md:inline-flex' => $row]) aria-hidden="true">{{ __('Read article') }} <x-site.icon name="arrow" class="arrow size-4" /></span>
    </div>
</article>
