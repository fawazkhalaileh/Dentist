@props(['treatment'])

{{-- One treatment: from a published service, or a sample category until the clinic publishes its own. --}}
<article class="group relative flex h-full flex-col rounded-3xl border border-line bg-ivory p-5 transition md:p-7 duration-300 hover:-translate-y-1 hover:border-wine/30 hover:bg-white hover:shadow-[0_24px_50px_-30px_rgb(78_2_23/0.35)]">
    @if ($treatment['media'])
        <x-ui.picture :media="$treatment['media']" alt="" sizes="(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 100vw" class="-mx-7 -mt-7 mb-6 aspect-[3/2] w-[calc(100%+3.5rem)] max-w-none rounded-t-3xl object-cover" />
    @else
        <span class="mb-4 flex size-12 items-center justify-center rounded-full md:mb-8 md:size-14 bg-blush text-wine transition group-hover:bg-wine group-hover:text-ivory">
            <x-site.icon :name="$treatment['icon']" class="size-7" />
        </span>
    @endif
    <h3 class="text-xl leading-snug md:text-2xl">
        <a href="{{ $treatment['url'] }}" class="after:absolute after:inset-0 after:rounded-3xl">{{ $treatment['title'] }}</a>
    </h3>
    @if ($treatment['summary'])
        <p class="mt-2 line-clamp-2 flex-1 text-sm leading-relaxed text-ink-muted md:mt-3 md:line-clamp-none md:text-[0.9375rem]">{{ $treatment['summary'] }}</p>
    @endif
    <span class="link-arrow mt-6 hidden text-sm md:inline-flex" aria-hidden="true">{{ __('Learn more') }} <x-site.icon name="arrow" class="arrow size-4" /></span>
</article>
