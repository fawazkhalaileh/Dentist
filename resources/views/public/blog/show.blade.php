@php $category = $post->category?->translation(); @endphp

<x-layouts::public
    :title="$translation->seo_title ?: $translation->title"
    :description="$translation->meta_description ?: $translation->excerpt"
    :alternates="$alternates"
    :image="$post->featuredMedia?->url()"
    type="article"
    :schema="$schema"
>
    <nav aria-label="{{ __('Breadcrumb') }}" class="mb-4 text-sm text-ink-muted">
        <a href="{{ route('public.blog.index') }}" class="hover:underline">{{ __('Blog') }}</a>
        @if ($category)
            <span aria-hidden="true"> / </span>
            <a href="{{ route('public.blog.category', ['slug' => $category->slug]) }}" class="hover:underline">{{ $category->name }}</a>
        @endif
    </nav>

    <article class="max-w-3xl">
        <header>
            <h1 class="text-3xl font-semibold">{{ $translation->title }}</h1>
            <p class="mt-2 text-sm text-ink-muted">
                <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->translatedFormat('j F Y') }}</time>
                · {{ $post->author->name }}
            </p>
        </header>

        @if ($post->featuredMedia)
            <x-ui.picture :media="$post->featuredMedia" :alt="$translation->title" eager sizes="(min-width: 768px) 768px, 100vw" class="mt-6 w-full rounded-lg" />
        @endif

        <x-ui.prose :markdown="$translation->body" class="mt-8" />
    </article>
</x-layouts::public>
