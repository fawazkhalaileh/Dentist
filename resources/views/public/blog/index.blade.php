@php
    /*
     * $posts: paginator of BlogPost (published) or, in sample mode, a collection of sample cards.
     * $categories: BlogCategory models, or [key => name] for samples.
     * $currentCategory: BlogCategoryTranslation, a sample category key, or null.
     */
    $categoryName = $sample ? ($currentCategory ? $categories[$currentCategory] : null) : $currentCategory?->name;
    $title = $categoryName ?: __('Dental Advice & Blog');
    $description = $categoryName
        ? __('Articles about :category from :clinic.', ['category' => $categoryName, 'clinic' => \App\Support\ClinicDetails::name()])
        : __('News and dental health articles from :clinic.', ['clinic' => \App\Support\ClinicDetails::name()]);

    $cards = $sample ? $posts : collect($posts->items())->map(function ($post) {
        $t = $post->translation();
        return [
            'title' => $t->title, 'excerpt' => $t->excerpt, 'url' => route('public.blog.show', ['slug' => $t->slug]),
            'category' => $post->category?->translation()?->name, 'date' => $post->published_at,
            'media' => $post->featuredMedia, 'photo' => null, 'sample' => false,
        ];
    });
    $showFeatured = ! $currentCategory && ! $search && (! $sample ? $posts->currentPage() === 1 : true) && $cards->count() > 2;
    $featured = $showFeatured ? $cards->first() : null;
    $rest = $showFeatured ? $cards->slice(1) : $cards;
    $filters = $sample
        ? collect($categories)->map(fn ($name, $key) => ['name' => $name, 'url' => route('public.blog.category', ['slug' => $key]), 'active' => $currentCategory === $key])->values()
        : $categories->map(function ($c) use ($currentCategory) { $ct = $c->translation(); return ['name' => $ct->name, 'url' => route('public.blog.category', ['slug' => $ct->slug]), 'active' => $currentCategory?->blog_category_id === $c->id]; });
@endphp

<x-layouts::public :title="$title" :description="$description" :alternates="$alternates ?? null" :bleed="true" :noindex="$sample && ($currentCategory || $search)">
    <x-site.page-hero :title="$title" :eyebrow="__('Dental advice')"
        :trail="$categoryName ? [[__('Blog'), route('public.blog.index')]] : []"
        :intro="__('Practical, easy-to-read guidance to help you look after your teeth between visits.')">
        <form method="GET" action="{{ url()->current() }}" role="search" class="mt-8 flex max-w-lg items-center gap-2 rounded-full border border-line bg-white p-1.5 ps-5 focus-within:border-wine">
            <label for="blog-search" class="sr-only">{{ __('Search articles') }}</label>
            <x-site.icon name="search" class="size-5 shrink-0 text-stone" />
            <input id="blog-search" name="q" type="search" value="{{ $search }}" maxlength="100" placeholder="{{ __('Search articles') }}" class="min-w-0 flex-1 bg-transparent py-2 outline-none" />
            <button type="submit" class="btn btn-primary btn-sm">{{ __('Search') }}</button>
        </form>
    </x-site.page-hero>

    <div class="site-container py-10 md:py-16">
        <div class="flex flex-wrap items-center justify-between gap-4">
            @if ($filters->isNotEmpty())
                <nav aria-label="{{ __('Blog categories') }}">
                    <ul class="flex flex-wrap gap-2 text-sm">
                        <li><a href="{{ route('public.blog.index') }}" @if (! $currentCategory) aria-current="page" @endif
                               @class(['inline-flex rounded-full border px-4 py-2 transition', 'border-wine bg-wine text-ivory' => ! $currentCategory, 'border-line hover:border-wine hover:text-wine' => $currentCategory])>{{ __('All') }}</a></li>
                        @foreach ($filters as $filter)
                            <li><a href="{{ $filter['url'] }}" @if ($filter['active']) aria-current="page" @endif
                                   @class(['inline-flex rounded-full border px-4 py-2 transition', 'border-wine bg-wine text-ivory' => $filter['active'], 'border-line hover:border-wine hover:text-wine' => ! $filter['active']])>{{ $filter['name'] }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endif
            @if ($sample)
                <span class="sample-badge">{{ __('Sample articles with demo dates, to be replaced by the clinic') }}</span>
            @endif
        </div>

        @if ($search)
            <p class="mt-8 text-ink-muted" role="status">
                {{ trans_choice(':count article found for “:term”|:count articles found for “:term”', $cards->count(), ['term' => $search]) }}
                <a href="{{ url()->current() }}" class="ms-2 text-wine underline underline-offset-4">{{ __('Clear search') }}</a>
            </p>
        @endif

        @if ($cards->isEmpty())
            <x-ui.empty-state class="mt-10" :title="$search ? __('No articles match your search') : __('No articles published yet')" />
        @else
            @if ($featured)
                <article class="group relative mt-12 grid overflow-hidden rounded-[2rem] bg-cream lg:grid-cols-2">
                    <div class="overflow-hidden">
                        @if ($featured['media'])
                            <x-ui.picture :media="$featured['media']" alt="" eager sizes="(min-width: 1024px) 50vw, 100vw" class="aspect-[4/3] size-full object-cover transition duration-700 group-hover:scale-[1.03]" />
                        @else
                            <x-site.photo :name="$featured['photo']" alt="" :eager="true" sizes="(min-width: 1024px) 50vw, 100vw" class="aspect-[4/3] size-full object-cover transition duration-700 group-hover:scale-[1.03]" />
                        @endif
                    </div>
                    <div class="flex flex-col justify-center p-8 md:p-14">
                        <p class="eyebrow">{{ __('Featured') }}{{ $featured['category'] ? ' · '.$featured['category'] : '' }}</p>
                        <h2 class="mt-5 text-4xl leading-tight md:text-5xl"><a href="{{ $featured['url'] }}" class="after:absolute after:inset-0">{{ $featured['title'] }}</a></h2>
                        @if ($featured['excerpt'])
                            <p class="mt-5 text-lg leading-relaxed text-ink-muted">{{ $featured['excerpt'] }}</p>
                        @endif
                        <p class="mt-6 flex flex-wrap items-center gap-3 text-sm text-stone">
                            @if ($featured['date'])
                                <time datetime="{{ $featured['date']->format('Y-m-d') }}">{{ $featured['date']->translatedFormat('j F Y') }}@if ($featured['sample']) · {{ __('demo date') }}@endif</time>
                            @endif
                            @if ($featured['sample'])<span class="sample-badge">{{ __('Sample article') }}</span>@endif
                        </p>
                        <span class="link-arrow mt-8" aria-hidden="true">{{ __('Read article') }} <x-site.icon name="arrow" class="arrow size-4" /></span>
                    </div>
                </article>
            @endif

            <ul class="mt-10 grid gap-x-8 gap-y-6 md:mt-14 md:grid-cols-2 md:gap-y-14 lg:grid-cols-3">
                @foreach ($rest as $card)
                    <li data-reveal><x-site.post-card :post="$card" :row="true" /></li>
                @endforeach
            </ul>

            @unless ($sample)
                <div class="mt-14">{{ $posts->links() }}</div>
            @endunless
        @endif
    </div>

    <x-site.cta />
</x-layouts::public>
