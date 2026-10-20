<x-layouts::public :title="$translation?->seo_title ?: ($translation?->title ?? $fallbackTitle)" :description="$translation?->meta_description" :bleed="true">
    <x-site.page-hero :title="$translation?->title ?? $fallbackTitle" />

    <div class="site-container max-w-4xl py-16 md:py-20">
        @if ($translation?->body)
            <x-ui.prose :markdown="$translation->body" />
        @else
            <x-ui.empty-state :title="__('This page is being prepared')" :description="__('Please contact the clinic if you need this information now.')" />
        @endif
    </div>
</x-layouts::public>
