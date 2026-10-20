@php
    $doctor = \App\Support\Site\SiteContent::doctorName();
    $hours = \App\Support\ClinicDetails::get('opening_hours');
    $featured = $cases->first();
@endphp

<x-layouts::public :title="__('Dental clinic in Amman')" :bleed="true" :description="__('Personalised dental care in a welcoming, modern clinic in Amman. Book an appointment with :clinic.', ['clinic' => \App\Support\ClinicDetails::name()])" :schema="$schema">

    {{-- 1. Hero --}}
    <section aria-labelledby="home-heading" class="relative overflow-hidden">
        <div aria-hidden="true" class="absolute inset-y-0 end-0 hidden w-[38%] bg-cream lg:block"></div>
        <div class="site-container relative grid items-center gap-8 pb-10 pt-6 lg:grid-cols-[1.05fr_1fr] lg:gap-16 lg:pb-16 lg:pt-10">
            <div>
                <p class="eyebrow">{{ __('Dental clinic in Amman') }}</p>
                <h1 id="home-heading" class="mt-4 text-5xl leading-[0.98] sm:text-7xl lg:text-[5.25rem]">
                    {{ __('Your Smile,') }}<br>
                    <em class="text-wine">{{ __('Our Passion.') }}</em>
                </h1>
                <p class="mt-5 max-w-xl text-lg leading-relaxed text-ink-muted md:mt-7 md:text-xl">
                    {{ __('Personalised dental care in a welcoming, modern environment. Discover treatment designed around your comfort, confidence, and long-term oral health.') }}
                </p>
                <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:flex-wrap md:mt-9">
                    <a href="{{ route('public.booking') }}" class="btn btn-primary">{{ __('Book an Appointment') }} <x-site.icon name="arrow" class="size-4 rtl:-scale-x-100" /></a>
                    <a href="{{ route('public.services.index') }}" class="btn btn-outline">{{ __('Explore Our Treatments') }}</a>
                </div>
                <ul class="mt-8 hidden max-w-lg grid-cols-3 gap-4 border-t border-line pt-6 text-sm text-ink-muted sm:grid">
                    <li class="flex items-center gap-3"><x-site.icon name="plan" class="size-5 shrink-0 text-wine" />{{ __('Personal treatment plans') }}</li>
                    <li class="flex items-center gap-3"><x-site.icon name="chat" class="size-5 shrink-0 text-wine" />{{ __('Arabic & English') }}</li>
                    <li class="flex items-center gap-3"><x-site.icon name="shield" class="size-5 shrink-0 text-wine" />{{ __('Strict hygiene') }}</li>
                </ul>
            </div>

            <div class="relative mx-auto w-full max-w-sm sm:max-w-md lg:max-w-none">
                <div class="relative ms-auto w-[86%] overflow-hidden rounded-t-[12rem] rounded-b-[2rem] shadow-[0_40px_80px_-40px_rgb(34_27_29/0.5)]">
                    <x-site.photo name="reception" :eager="true" sizes="(min-width: 1024px) 40vw, 86vw" class="aspect-[5/4] w-full object-cover sm:aspect-[4/5] lg:aspect-[5/6]" />
                </div>
                <div class="absolute -bottom-6 start-0 hidden w-[42%] overflow-hidden rounded-[1.5rem] border-[6px] border-ivory shadow-xl sm:block">
                    <x-site.photo name="treatment-room" sizes="(min-width: 1024px) 18vw, 40vw" class="aspect-square w-full object-cover" />
                </div>
                @if ($hours)
                    <div class="absolute end-0 top-8 hidden max-w-[15rem] rounded-2xl bg-ivory/95 p-5 shadow-xl backdrop-blur sm:block lg:-end-6">
                        <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.14em] text-wine"><x-site.icon name="clock" class="size-4" />{{ __('Opening hours') }}</p>
                        <p class="mt-2 whitespace-pre-line text-sm leading-relaxed">{{ $hours }}</p>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- 2. Welcome and why patients choose us, in one section --}}
    <section aria-labelledby="welcome-heading" class="py-12 md:py-20">
        <div class="site-container grid items-center gap-10 lg:grid-cols-[1fr_1.15fr] lg:gap-16">
            <x-site.photo name="treatment-room" sizes="(min-width: 1024px) 42vw, 100vw" class="hidden aspect-[4/3] w-full rounded-[2rem] object-cover lg:block" />
            <div>
                <x-site.heading :eyebrow="__('Welcome to our clinic')" :title="__('A calm place to look after your smile')" id="welcome-heading">
                    {{ __('From your first visit, we take time to understand what matters to you, explain your options clearly and plan treatment at a pace that feels right.') }}
                </x-site.heading>
                <ul class="mt-7 grid grid-cols-2 gap-x-6 gap-y-5">
                    @foreach ([
                        ['heart', __('Personalised Dental Care')],
                        ['sparkle', __('Modern, Comfortable Environment')],
                        ['plan', __('Clear Treatment Guidance')],
                        ['chat', __('A Patient-First Experience')],
                    ] as [$icon, $title])
                        <li class="flex items-center gap-3">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-blush text-wine"><x-site.icon :name="$icon" class="size-5" /></span>
                            <span class="font-medium leading-snug">{{ $title }}</span>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-7 flex flex-wrap items-center gap-4">
                    <a href="{{ route('public.about') }}" class="link-arrow">{{ __('More about us') }} <x-site.icon name="arrow" class="arrow size-4" /></a>
                    <span class="sample-badge">{{ __('Placeholder copy, awaiting approval') }}</span>
                </div>
            </div>
        </div>
    </section>

    {{-- 3. Meet the doctor --}}
    <section aria-labelledby="doctor-heading" class="bg-cream py-12 md:py-16">
        <div class="site-container grid items-center gap-8 md:grid-cols-[0.9fr_1.1fr] md:gap-14">
            <x-site.photo name="doctor" sizes="(min-width: 768px) 45vw, 100vw" class="aspect-[16/10] w-full rounded-[2rem] object-cover object-[58%_40%] md:aspect-[4/3]" />
            <div>
                <x-site.heading :eyebrow="__('Meet your dentist')" :title="$doctor" id="doctor-heading">
                    {{ __('Every smile is different, and so is every patient. :name believes good dentistry starts with a proper conversation: understanding how you feel, what you hope for and what worries you.', ['name' => $doctor]) }}
                </x-site.heading>
                <div class="mt-7 flex flex-wrap items-center gap-4">
                    <a href="{{ route('public.doctor') }}" class="btn btn-primary">{{ __('Meet the Doctor') }}</a>
                    <span class="sample-badge">{{ __('Placeholder biography') }}</span>
                </div>
            </div>
        </div>
    </section>

    {{-- 4. Treatments: compact tiles --}}
    <section aria-labelledby="treatments-heading" class="py-12 md:py-20">
        <div class="site-container">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">{{ __('Our treatments') }}</p>
                    <h2 id="treatments-heading" class="mt-3 text-4xl md:text-5xl">{{ __('Care for every stage of your smile') }}</h2>
                </div>
                <a href="{{ route('public.services.index') }}" class="link-arrow">{{ __('View all treatments') }} <x-site.icon name="arrow" class="arrow size-4" /></a>
            </div>
            @if ($treatments->contains('sample', true))
                <p class="mt-4"><span class="sample-badge">{{ __('Sample treatments, to be confirmed by the clinic') }}</span></p>
            @endif
            <ul class="mt-8 grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-4">
                @foreach ($treatments as $treatment)
                    <li>
                        <a href="{{ $treatment['url'] }}" class="group flex h-full items-center gap-3 rounded-2xl border border-line bg-white/50 p-3 transition hover:border-wine/40 hover:bg-white md:gap-4 md:p-4">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-blush text-wine transition group-hover:bg-wine group-hover:text-ivory md:size-12"><x-site.icon :name="$treatment['icon']" class="size-5 md:size-6" /></span>
                            <span class="text-sm font-medium leading-snug md:text-base">{{ $treatment['title'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- 5. Smile transformations --}}
    <section aria-labelledby="smiles-heading" class="overflow-hidden bg-night py-12 text-ivory md:py-20">
        <div class="site-container grid items-center gap-8 lg:grid-cols-[1fr_1.25fr] lg:gap-16">
            <div>
                <x-site.heading :eyebrow="__('Smile transformations')" :title="__('Real results, shared with permission')" id="smiles-heading" :light="true">
                    {{ __('Drag the handle to compare before and after. Every case is planned around the patient’s own teeth, face and goals.') }}
                </x-site.heading>
                <a href="{{ route('public.gallery') }}" class="btn btn-light mt-7">{{ __('View All Transformations') }}</a>
                <p class="mt-5 text-xs leading-relaxed text-ivory/60">{{ __('Individual results vary. Photos show individual patients and are not a guarantee of a similar outcome.') }}</p>
            </div>
            <div>
                <x-site.compare :before="$featured?->beforeMedia" :after="$featured?->afterMedia" />
                @if ($featured)
                    <p class="mt-3 text-sm text-ivory/70">{{ $featured->translation()?->title }}</p>
                @else
                    <p class="mt-3"><span class="sample-badge !bg-transparent !text-ivory/70">{{ __('Placeholder until consent is confirmed') }}</span></p>
                @endif
            </div>
        </div>
    </section>

    {{-- 6. Testimonials --}}
    <section aria-labelledby="testimonials-heading" class="py-12 md:py-20" data-carousel>
        <div class="site-container">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">{{ __('Patient stories') }}</p>
                    <h2 id="testimonials-heading" class="mt-3 text-4xl md:text-5xl">{{ __('Kind words from our patients') }}</h2>
                </div>
                <div class="flex gap-2">
                    <button type="button" data-carousel-prev class="flex size-11 items-center justify-center rounded-full border border-wine/30 text-wine transition hover:bg-wine hover:text-ivory">
                        <x-site.icon name="arrow" class="size-5 -scale-x-100 rtl:scale-x-100" /><span class="sr-only">{{ __('Previous') }}</span>
                    </button>
                    <button type="button" data-carousel-next class="flex size-11 items-center justify-center rounded-full border border-wine/30 text-wine transition hover:bg-wine hover:text-ivory">
                        <x-site.icon name="arrow" class="size-5 rtl:-scale-x-100" /><span class="sr-only">{{ __('Next') }}</span>
                    </button>
                </div>
            </div>
            @if ($testimonials->contains('sample', true))
                <p class="mt-4"><span class="sample-badge">{{ __('Sample testimonials for design only, not real reviews') }}</span></p>
            @endif
            <ul data-carousel-track tabindex="0" class="-mx-5 mt-7 flex snap-x snap-mandatory gap-4 overflow-x-auto rounded-2xl px-5 pb-2 [scrollbar-width:none] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-wine md:mx-0 md:gap-6 md:px-0" aria-label="{{ __('Testimonials') }}">
                @foreach ($testimonials as $testimonial)
                    <li class="w-[82%] shrink-0 snap-start sm:w-[55%] lg:w-[calc((100%-3rem)/3)]">
                        <figure class="flex h-full flex-col rounded-[1.5rem] bg-cream p-6 md:p-8">
                            <x-site.icon name="quote" class="size-7 text-wine/70" />
                            <blockquote class="mt-4 flex-1 font-display text-xl leading-snug">{{ $testimonial['body'] }}</blockquote>
                            <figcaption class="mt-5 flex items-center justify-between gap-3 text-sm">
                                <span class="font-medium">{{ $testimonial['author'] }}</span>
                                @if ($testimonial['sample'])
                                    <span class="sample-badge">{{ __('Sample') }}</span>
                                @endif
                            </figcaption>
                        </figure>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- 7. From the blog: one swipeable row on phones --}}
    <section aria-labelledby="blog-heading" class="border-t border-line py-12 md:py-20">
        <div class="site-container">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">{{ __('Dental advice') }}</p>
                    <h2 id="blog-heading" class="mt-3 text-4xl md:text-5xl">{{ __('From the blog') }}</h2>
                </div>
                <a href="{{ route('public.blog.index') }}" class="link-arrow">{{ __('All articles') }} <x-site.icon name="arrow" class="arrow size-4" /></a>
            </div>
            <ul class="-mx-5 mt-7 flex snap-x snap-mandatory gap-5 overflow-x-auto px-5 pb-2 [scrollbar-width:none] md:mx-0 md:grid md:grid-cols-3 md:gap-8 md:overflow-visible md:px-0" tabindex="0" aria-label="{{ __('From the blog') }}">
                @foreach ($posts as $post)
                    <li class="w-[78%] shrink-0 snap-start sm:w-[50%] md:w-auto"><x-site.post-card :post="$post" /></li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- 8. Appointment call to action --}}
    <x-site.cta class="pt-0 md:pt-0" />
</x-layouts::public>
