@php
    // Filter chips come from the treatments linked to published cases.
    $filters = $items->map(fn ($i) => $i->service?->translation())->filter()->unique('service_id')
        ->mapWithKeys(fn ($t) => [$t->service_id => $t->title]);
@endphp

<x-layouts::public :title="__('Smile Gallery')" :bleed="true" :description="__('Before and after photos of treatment at :clinic, shared with each patient\'s consent.', ['clinic' => \App\Support\ClinicDetails::name()])">
    <x-site.page-hero :title="__('Smile Gallery')" :eyebrow="__('Smile transformations')"
        :intro="__('Real results from real patients, shared only with their written permission. Drag the handle on any photo to compare before and after.')" />

    <section aria-labelledby="cases-heading" class="py-14 md:py-20">
        <div class="site-container">
            <h2 id="cases-heading" class="sr-only">{{ __('Before and after cases') }}</h2>

            @if ($items->isEmpty())
                <div class="grid gap-10 lg:grid-cols-[1fr_1.4fr] lg:items-center">
                    <div>
                        <span class="sample-badge">{{ __('Placeholder until consent is confirmed') }}</span>
                        <p class="mt-6 font-display text-3xl leading-snug">{{ __('Our patients’ results will appear here soon.') }}</p>
                        <p class="mt-4 leading-relaxed text-ink-muted">{{ __('We only publish before and after photos once the patient has given written permission. Until then, these neutral placeholders show how the gallery will work.') }}</p>
                        <a href="{{ route('public.booking') }}" class="btn btn-primary mt-8">{{ __('Book a Consultation') }}</a>
                    </div>
                    <x-site.compare :label="__('Placeholder comparison')" />
                </div>
                <ul class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3" aria-hidden="true">
                    @foreach (range(1, 3) as $i)
                        <li class="rounded-[2rem] border border-dashed border-sand p-3">
                            <div dir="ltr" class="grid aspect-[4/3] grid-cols-2 overflow-hidden rounded-[1.5rem]">
                                <div class="flex items-end bg-[#e6d9cb] p-3 text-xs font-medium text-stone">{{ __('Before') }}</div>
                                <div class="flex items-end justify-end bg-[#f3ece2] p-3 text-xs font-medium text-stone">{{ __('After') }}</div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                @if ($filters->count() > 1)
                    <div class="flex flex-wrap gap-2 text-sm" data-gallery-filters role="group" aria-label="{{ __('Filter by treatment') }}">
                        <button type="button" data-filter="all" aria-pressed="true" class="rounded-full border border-line px-4 py-2 transition aria-pressed:border-wine aria-pressed:bg-wine aria-pressed:text-ivory">{{ __('All') }}</button>
                        @foreach ($filters as $id => $name)
                            <button type="button" data-filter="{{ $id }}" aria-pressed="false" class="rounded-full border border-line px-4 py-2 transition hover:border-wine aria-pressed:border-wine aria-pressed:bg-wine aria-pressed:text-ivory">{{ $name }}</button>
                        @endforeach
                    </div>
                @endif

                <ul class="mt-10 grid gap-x-6 gap-y-12 md:grid-cols-2" data-gallery>
                    @foreach ($items as $index => $item)
                        @php $t = $item->translation(); @endphp
                        <li id="case-{{ $item->id }}" data-category="{{ $item->service_id ?? 'none' }}" class="scroll-mt-28">
                            <figure>
                                <x-site.compare :before="$item->beforeMedia" :after="$item->afterMedia" :label="__('Compare before and after: :title', ['title' => $t->title])" />
                                <figcaption class="mt-5 flex items-start justify-between gap-4">
                                    <span>
                                        <span class="block font-display text-2xl">{{ $t->title }}</span>
                                        @if ($item->service?->translation())
                                            <span class="mt-1 block text-sm text-wine">{{ $item->service->translation()->title }}</span>
                                        @endif
                                        @if ($t->description)
                                            <span class="mt-2 block text-sm leading-relaxed text-ink-muted">{{ $t->description }}</span>
                                        @endif
                                    </span>
                                    <button type="button" data-open-case="{{ $index }}" class="flex size-11 shrink-0 items-center justify-center rounded-full border border-line text-wine transition hover:border-wine">
                                        <x-site.icon name="expand" class="size-5" /><span class="sr-only">{{ __('View full screen: :title', ['title' => $t->title]) }}</span>
                                    </button>
                                </figcaption>
                            </figure>
                        </li>
                    @endforeach
                </ul>

                {{-- Full-screen viewer: native <dialog> (Escape closes it); arrow keys move between cases. --}}
                <dialog data-case-viewer aria-label="{{ __('Smile Gallery') }}" class="m-auto size-full max-h-none max-w-none bg-night/95 p-0 text-ivory backdrop:bg-night/80">
                    <div class="flex h-full flex-col">
                        <div class="flex items-center justify-between p-4 md:p-6">
                            <p class="text-sm text-ivory/70" data-case-counter aria-live="polite"></p>
                            <form method="dialog"><button class="flex size-11 items-center justify-center rounded-full border border-ivory/30 hover:border-ivory"><x-site.icon name="close" /><span class="sr-only">{{ __('Close') }}</span></button></form>
                        </div>
                        <div class="relative flex flex-1 items-center justify-center px-4 pb-6 md:px-20">
                            @foreach ($items as $index => $item)
                                <figure data-case="{{ $index }}" hidden class="w-full max-w-5xl">
                                    <x-site.compare :before="$item->beforeMedia" :after="$item->afterMedia" sizes="(min-width: 1024px) 1024px, 100vw" class="mx-auto max-w-[calc(75vh*4/3)]" />
                                    <figcaption class="mt-4 text-center font-display text-2xl">{{ $item->translation()->title }}</figcaption>
                                </figure>
                            @endforeach
                            <button type="button" data-case-prev class="absolute start-2 top-1/2 flex size-12 -translate-y-1/2 items-center justify-center rounded-full bg-ivory/10 hover:bg-ivory/20 md:start-6"><x-site.icon name="arrow" class="size-5 -scale-x-100 rtl:scale-x-100" /><span class="sr-only">{{ __('Previous') }}</span></button>
                            <button type="button" data-case-next class="absolute end-2 top-1/2 flex size-12 -translate-y-1/2 items-center justify-center rounded-full bg-ivory/10 hover:bg-ivory/20 md:end-6"><x-site.icon name="arrow" class="size-5 rtl:-scale-x-100" /><span class="sr-only">{{ __('Next') }}</span></button>
                        </div>
                    </div>
                </dialog>
            @endif

            <p class="mt-14 max-w-3xl text-sm leading-relaxed text-stone">{{ __('Individual results vary. Photos show individual patients and are not a guarantee of a similar outcome. Images are shown as taken, without retouching of the teeth.') }}</p>
        </div>
    </section>

    <x-site.cta />
</x-layouts::public>
