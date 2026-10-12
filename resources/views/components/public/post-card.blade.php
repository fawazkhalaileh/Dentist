@props(['post'])

@php
    $t = $post->translation();
    $category = $post->category?->translation();
@endphp

<article class="flex h-full flex-col overflow-hidden rounded-lg border border-line">
    @if ($post->featuredMedia)
        <x-ui.picture :media="$post->featuredMedia" alt="" sizes="(min-width: 768px) 33vw, 100vw" class="aspect-[3/2] w-full object-cover" />
    @endif
    <div class="flex flex-1 flex-col p-5">
        <p class="text-xs text-ink-muted">
            <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->translatedFormat('j F Y') }}</time>
            @if ($category)
                · <a href="{{ route('public.blog.category', ['slug' => $category->slug]) }}" class="hover:underline">{{ $category->name }}</a>
            @endif
        </p>
        <h3 class="mt-2 text-lg font-semibold">
            <a href="{{ route('public.blog.show', ['slug' => $t->slug]) }}" class="hover:underline">{{ $t->title }}</a>
        </h3>
        @if ($t->excerpt)
            <p class="mt-2 text-sm text-ink-muted">{{ $t->excerpt }}</p>
        @endif
    </div>
</article>
