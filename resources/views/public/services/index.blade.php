<x-layouts::public :title="__('Treatments')" :bleed="true"
    :description="__('Dental treatments at :clinic in Amman, from check-ups and whitening to veneers, implants and alignment.', ['clinic' => \App\Support\ClinicDetails::name()])">

    <x-site.page-hero :title="__('Our Treatments')" :eyebrow="__('Treatments')" photo="treatment-room"
        :intro="__('Every treatment begins with a consultation, a clear explanation of your options and a plan made around you.')">
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('public.booking') }}" class="btn btn-primary">{{ __('Book a Consultation') }}</a>
            <a href="{{ route('public.contact') }}" class="btn btn-outline">{{ __('Ask a question') }}</a>
        </div>
    </x-site.page-hero>

    <section aria-labelledby="treatment-list" class="py-12 md:py-20">
        <div class="site-container">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h2 id="treatment-list" class="text-4xl">{{ __('Find the right care') }}</h2>
                @if ($treatments->contains('sample', true))
                    <span class="sample-badge">{{ __('Sample treatments, to be confirmed by the clinic') }}</span>
                @endif
            </div>

            @if ($treatments->isEmpty())
                <x-ui.empty-state class="mt-10" :title="__('No services published yet')" :description="__('Please check back soon or contact the clinic.')" />
            @else
                <ul class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($treatments as $treatment)
                        <li data-reveal><x-site.treatment-card :treatment="$treatment" /></li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    <section aria-labelledby="prices-heading" class="border-t border-line bg-cream py-12">
        <div class="site-container">
            <h2 id="prices-heading" class="text-4xl">{{ __('Prices') }}</h2>
            <div class="mt-8 grid gap-x-12 gap-y-8 md:grid-cols-2 lg:grid-cols-3">
                @foreach (config('site.prices') as $group)
                    <div>
                        <h3 class="text-2xl text-wine">{{ $group['label'][app()->getLocale()] }}</h3>
                        <dl class="mt-3 divide-y divide-line">
                            @foreach ($group['items'] as $item)
                                <div class="flex items-baseline justify-between gap-4 py-3">
                                    <dt class="min-w-0 text-sm">
                                        {{ $item['label'][app()->getLocale()] }}
                                        @if (isset($item['note']))
                                            <span class="mt-1 block text-xs text-ink-muted">{{ $item['note'][app()->getLocale()] }}</span>
                                        @endif
                                    </dt>
                                    <dd class="shrink-0 text-right text-sm font-semibold tabular-nums {{ $item['amount'] === null ? 'max-w-36 whitespace-normal' : 'whitespace-nowrap' }}">
                                        @if ($item['amount'] === null)
                                            {{ __('Price depends on your treatment plan') }}
                                        @else
                                            <bdi>{{ $item['from'] ? __('From :price', ['price' => \App\Support\Site\Prices::amount($item['amount'])]) : \App\Support\Site\Prices::amount($item['amount']) }}</bdi>
                                        @endif
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endforeach
            </div>
            <p class="mt-6 text-sm text-ink-muted">{{ __('Prices are a guide. Your exact price is confirmed after an examination.') }}</p>
        </div>
    </section>

    <section aria-labelledby="how-heading" class="bg-night py-20 text-ivory md:py-24">
        <div class="site-container">
            <x-site.heading :eyebrow="__('Your first visit')" :title="__('How treatment works with us')" id="how-heading" :light="true" />
            <ol class="mt-14 grid gap-10 md:grid-cols-4">
                @foreach ([
                    [__('Talk'), __('Tell us what brings you in and what you would like to achieve.')],
                    [__('Examine'), __('A careful check of your teeth and gums, with X-rays only when needed.')],
                    [__('Plan'), __('We explain your options, timings and costs before anything begins.')],
                    [__('Treat'), __('Care at a pace that suits you, with follow-up afterwards.')],
                ] as $i => [$title, $text])
                    <li class="border-t border-ivory/20 pt-6">
                        <span class="font-display text-5xl text-ivory/40">0{{ $i + 1 }}</span>
                        <h3 class="mt-4 text-2xl">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ivory/70">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <x-site.cta />
</x-layouts::public>
