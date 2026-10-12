<x-layouts::public :title="$translation?->seo_title ?: ($translation?->title ?? $fallbackTitle)" :description="$translation?->meta_description">
    <article class="max-w-3xl">
        <h1 class="text-3xl font-semibold">{{ $translation?->title ?? $fallbackTitle }}</h1>

        @if ($translation?->body)
            <x-ui.prose :markdown="$translation->body" class="mt-6" />
        @else
            <x-ui.empty-state class="mt-6" :title="__('This page is being prepared')" :description="__('Please contact the clinic if you need this information now.')" />
        @endif
    </article>
</x-layouts::public>
