@php
    $category = $post->category?->translation();
    $cards = $related->map(function ($p) {
        $t = $p->translation();
        return [
            'title' => $t->title, 'excerpt' => $t->excerpt, 'url' => route('public.blog.show', ['slug' => $t->slug]),
            'category' => $p->category?->translation()?->name, 'date' => $p->published_at,
            'media' => $p->featuredMedia, 'photo' => null, 'sample' => false,
        ];
    });
    $words = str_word_count(strip_tags($translation->body)) ?: (int) (mb_strlen($translation->body) / 6);
@endphp

<x-layouts::public
    :title="$translation->seo_title ?: $translation->title"
    :description="$translation->meta_description ?: $translation->excerpt"
    :alternates="$alternates"
    :image="$post->featuredMedia?->url()"
    type="article"
    :schema="$schema"
    :bleed="true"
>
    <x-site.article :title="$translation->title" :excerpt="$translation->excerpt" :category="$category?->name"
        :category-url="$category ? route('public.blog.category', ['slug' => $category->slug]) : null"
        :date="$post->published_at" :author="$post->author->name" :minutes="max(1, (int) round($words / 200))"
        :media="$post->featuredMedia" :related="$cards">
        <x-ui.prose :markdown="$translation->body" />
    </x-site.article>
</x-layouts::public>
