@php
    $title = $currentCategory ? $currentCategory->name : __('Blog');
    $description = $currentCategory
        ? __('Articles about :category from :clinic.', ['category' => $currentCategory->name, 'clinic' => config('clinic.name')])
        : __('News and dental health articles from :clinic.', ['clinic' => config('clinic.name')]);
@endphp

<x-layouts::public :title="$title" :description="$description" :alternates="$alternates ?? null">
    <x-ui.page-header :title="$title" />

    @if ($categories->isNotEmpty())
        <nav aria-label="{{ __('Blog categories') }}" class="mb-8">
            <ul class="flex flex-wrap gap-2 text-sm">
                <li>
                    <a href="{{ route('public.blog.index') }}" @if (! $currentCategory) aria-current="page" @endif
                       @class(['inline-block rounded-full border px-3 py-1', 'border-brand bg-brand text-brand-contrast' => ! $currentCategory, 'border-line hover:bg-surface-muted' => $currentCategory])>
                        {{ __('All') }}
                    </a>
                </li>
                @foreach ($categories as $category)
                    @php $ct = $category->translation(); $active = $currentCategory?->blog_category_id === $category->id; @endphp
                    <li>
                        <a href="{{ route('public.blog.category', ['slug' => $ct->slug]) }}" @if ($active) aria-current="page" @endif
                           @class(['inline-block rounded-full border px-3 py-1', 'border-brand bg-brand text-brand-contrast' => $active, 'border-line hover:bg-surface-muted' => ! $active])>
                            {{ $ct->name }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    @endif

    @if ($posts->isEmpty())
        <x-ui.empty-state :title="__('No articles published yet')" />
    @else
        <ul class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($posts as $post)
                <li><x-public.post-card :post="$post" /></li>
            @endforeach
        </ul>

        <div class="mt-8">{{ $posts->links() }}</div>
    @endif
</x-layouts::public>
