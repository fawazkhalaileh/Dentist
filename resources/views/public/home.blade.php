@php
    $doctor = \App\Support\Site\SiteContent::doctorName();
    $hours = \App\Support\ClinicDetails::get('opening_hours');
    $address = \App\Support\ClinicDetails::get('address');
    $featured = $cases->first();
@endphp

<x-layouts::public :title="null" :bleed="true" :description="__('Personalised dental care in a welcoming, modern clinic in Amman. Book an appointment with :clinic.', ['clinic' => \App\Support\ClinicDetails::name()])" :schema="$schema">

    {{-- 1. Hero --}}
    <section aria-labelledby="home-heading" class="relative overflow-hidden">
        <div aria-hidden="true" class="absolute inset-y-0 end-0 hidden w-[38%] bg-cream lg:block"></div>
        <div class="site-container relative grid items-center gap-12 pb-16 pt-10 lg:grid-cols-[1.05fr_1fr] lg:gap-16 lg:pb-24 lg:pt-16">
            <div>
                <p class="eyebrow">{{ __('Dental clinic in Amman') }}</p>
                <h1 id="home-heading" class="mt-6 text-[3.25rem] leading-[0.98] sm:text-7xl lg:text-[5.5rem]">
                    {{ __('Your Smile,') }}<br>
                    <em class="text-wine">{{ __('Our Passion.') }}</em>
                </h1>
                <p class="mt-8 max-w-xl text-lg leading-relaxed text-ink-muted md:text-xl">
                    {{ __('Personalised dental care in a welcoming, modern environment. Discover treatment designed around your comfort, confidence, and long-term oral health.') }}
                </p>
                <div class="mt-10 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('public.booking') }}" class="btn btn-primary">{{ __('Book an Appointment') }} <x-site.icon name="arrow" class="size-4 rtl:-scale-x-100" /></a>
                    <a href="{{ route('public.services.index') }}" class="btn btn-outline">{{ __('Explore Our Treatments') }}</a>
                </div>
                <ul class="mt-12 grid max-w-lg grid-cols-1 gap-4 border-t border-line pt-8 text-sm text-ink-muted sm:grid-cols-3">
                    <li class="flex items-center gap-3"><x-site.icon name="plan" class="size-5 shrink-0 text-wine" />{{ __('Personal treatment plans') }}</li>
                    <li class="flex items-center gap-3"><x-site.icon name="chat" class="size-5 shrink-0 text-wine" />{{ __('Arabic & English') }}</li>
                    <li class="flex items-center gap-3"><x-site.icon name="shield" class="size-5 shrink-0 text-wine" />{{ __('Strict hygiene') }}</li>
                </ul>
            </div>

            <div class="relative mx-auto w-full max-w-md lg:max-w-none">
                <div class="relative ms-auto w-[86%] overflow-hidden rounded-t-[12rem] rounded-b-[2rem] shadow-[0_40px_80px_-40px_rgb(34_27_29/0.5)]">
                    <x-site.photo name="reception" :eager="true" sizes="(min-width: 1024px) 40vw, 86vw" class="aspect-[4/5] w-full object-cover" />
                </div>
                <div class="absolute -bottom-8 start-0 w-[46%] overflow-hidden rounded-[1.5rem] border-[6px] border-ivory shadow-xl">
                    <x-site.photo name="treatment-room" sizes="(min-width: 1024px) 20vw, 40vw" class="aspect-square w-full object-cover" />
                </div>
                @if ($hours)
                    <div class="absolute end-0 top-10 hidden max-w-[15rem] rounded-2xl bg-ivory/95 p-5 shadow-xl backdrop-blur sm:block lg:-end-6">
                        <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.14em] text-wine"><x-site.icon name="clock" class="size-4" />{{ __('Opening hours') }}</p>
                        <p class="mt-2 whitespace-pre-line text-sm leading-relaxed">{{ $hours }}</p>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- 2. Welcome --}}
    <section aria-labelledby="welcome-heading" class="py-20 md:py-28">
        <div class="site-container grid items-center gap-12 lg:grid-cols-2 lg:gap-20">
            <div class="relative order-2 lg:order-1" data-reveal>
                <x-site.photo name="treatment-room" sizes="(min-width: 1024px) 45vw, 100vw" class="aspect-[5/4] w-full rounded-[2rem] object-cover" />
                <div class="absolute -bottom-6 end-6 flex items-center gap-4 rounded-2xl bg-ivory px-5 py-4 shadow-xl md:end-10">
                    <img src="{{ asset('images/brand/mark-brand.webp') }}" width="123" height="128" alt="" class="h-11 w-auto" />
                    <p class="text-sm leading-snug"><span class="block font-display text-xl">{{ __('Care that starts') }}</span><span class="text-ink-muted">{{ __('with listening') }}</span></p>
                </div>
            </div>
            <div class="order-1 lg:order-2" data-reveal>
                <x-site.heading :eyebrow="__('Welcome to our clinic')" :title="__('A calm place to look after your smile')" id="welcome-heading">
                    {{ __('From your first visit, we take time to understand what matters to you, explain your options clearly and plan treatment at a pace that feels right.') }}
                </x-site.heading>
                <ul class="mt-8 grid gap-5 sm:grid-cols-2">
                    @foreach ([['heart', __('Gentle, unhurried appointments')], ['plan', __('Clear plans before treatment begins')], ['sparkle', __('A bright, modern clinic')], ['leaf', __('Care for every age')]] as [$icon, $text])
                        <li class="flex items-start gap-3">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-blush text-wine"><x-site.icon :name="$icon" class="size-5" /></span>
                            <span class="pt-2 font-medium">{{ $text }}</span>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('public.about') }}" class="link-arrow mt-10">{{ __('More about us') }} <x-site.icon name="arrow" class="arrow size-4" /></a>
                <p class="mt-6"><span class="sample-badge">{{ __('Placeholder copy, awaiting approval') }}</span></p>
            </div>
        </div>
    </section>

    {{-- 3. Meet the doctor --}}
    <section aria-labelledby="doctor-heading" class="overflow-hidden bg-cream py-20 md:py-28">
        <div class="site-container grid items-center gap-12 lg:grid-cols-[1fr_1.1fr] lg:gap-20">
            <div data-reveal>
                <x-site.heading :eyebrow="__('Meet your dentist')" :title="$doctor" id="doctor-heading">
                    {{ __('Every smile is different, and so is every patient. :name believes good dentistry starts with a proper conversation: understanding how you feel, what you hope for and what worries you.', ['name' => $doctor]) }}
                </x-site.heading>
                <blockquote class="mt-8 border-s-2 border-wine ps-6 font-display text-2xl italic leading-snug text-ink">
                    “{{ __('My goal is simple: that you leave every visit feeling comfortable, informed and confident about your smile.') }}”
                </blockquote>
                <p class="mt-6 text-ink-muted">{{ __('Short biography to be supplied by the clinic. Qualifications and professional background will be added once verified.') }}</p>
                <div class="mt-10 flex flex-wrap items-center gap-4">
                    <a href="{{ route('public.doctor') }}" class="btn btn-primary">{{ __('Meet the Doctor') }}</a>
                    <span class="sample-badge">{{ __('Placeholder biography') }}</span>
                </div>
            </div>
            <div class="relative" data-reveal>
                <div aria-hidden="true" class="absolute -inset-4 -z-0 translate-x-6 translate-y-6 rounded-[2.5rem] border border-wine/20 rtl:-translate-x-6"></div>
                <x-site.photo name="doctor" sizes="(min-width: 1024px) 50vw, 100vw" class="relative aspect-[4/3] w-full rounded-[2rem] object-cover object-[58%_40%]" />
            </div>
        </div>
    </section>

    {{-- 4. Treatments --}}
    <section aria-labelledby="treatments-heading" class="py-20 md:py-28">
        <div class="site-container">
            <div class="flex flex-col justify-between gap-8 md:flex-row md:items-end">
                <x-site.heading :eyebrow="__('Our treatments')" :title="__('Care for every stage of your smile')" id="treatments-heading">
                    {{ __('From routine check-ups to complete smile makeovers, every treatment begins with a consultation and a plan made for you.') }}
                </x-site.heading>
                <a href="{{ route('public.services.index') }}" class="btn btn-outline shrink-0 self-start md:self-auto">{{ __('View all treatments') }}</a>
            </div>
            @if ($treatments->contains('sample', true))
                <p class="mt-6"><span class="sample-badge">{{ __('Sample treatments, to be confirmed by the clinic') }}</span></p>
            @endif
            <ul class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($treatments as $treatment)
                    <li data-reveal><x-site.treatment-card :treatment="$treatment" /></li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- 5. Smile transformations --}}
    <section aria-labelledby="smiles-heading" class="overflow-hidden bg-night py-20 text-ivory md:py-28">
        <div class="site-container grid items-center gap-12 lg:grid-cols-[1fr_1.25fr] lg:gap-20">
            <div data-reveal>
                <x-site.heading :eyebrow="__('Smile transformations')" :title="__('Real results, shared with permission')" id="smiles-heading" :light="true">
                    {{ __('Drag the handle to compare before and after. Every case is planned around the patient’s own teeth, face and goals.') }}
                </x-site.heading>
                @if ($cases->isNotEmpty())
                    <ul class="mt-10 grid grid-cols-3 gap-3">
                        @foreach ($cases->skip(1)->take(3) as $case)
                            <li>
                                <a href="{{ route('public.gallery') }}#case-{{ $case->id }}" class="block overflow-hidden rounded-2xl ring-1 ring-ivory/10 transition hover:ring-ivory/60">
                                    <x-ui.picture :media="$case->afterMedia" alt="{{ $case->translation()?->title }}" sizes="160px" class="aspect-square w-full object-cover" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-8 rounded-2xl border border-ivory/15 p-5 text-sm leading-relaxed text-ivory/75">
                        {{ __('Patient photos appear here only after the patient has given permission to publish them. Until then, this is a placeholder.') }}
                    </p>
                @endif
                <a href="{{ route('public.gallery') }}" class="btn btn-light mt-10">{{ __('View All Transformations') }}</a>
                <p class="mt-6 text-xs leading-relaxed text-ivory/50">{{ __('Individual results vary. Photos show individual patients and are not a guarantee of a similar outcome.') }}</p>
            </div>
            <div data-reveal>
                <x-site.compare :before="$featured?->beforeMedia" :after="$featured?->afterMedia" />
                @if ($featured)
                    <p class="mt-4 text-sm text-ivory/70">{{ $featured->translation()?->title }}</p>
                @else
                    <p class="mt-4"><span class="sample-badge !bg-transparent !text-ivory/60">{{ __('Placeholder until consent is confirmed') }}</span></p>
                @endif
            </div>
        </div>
    </section>

    {{-- 6. Why choose us --}}
    <section aria-labelledby="why-heading" class="py-20 md:py-28">
        <div class="site-container">
            <x-site.heading :eyebrow="__('Why patients choose us')" :title="__('Dentistry that feels personal')" id="why-heading" align="center" />
            <ul class="mt-16 grid gap-px overflow-hidden rounded-[2rem] border border-line bg-line sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['heart', __('Personalised Dental Care'), __('Your plan is built around your teeth, your goals and your budget, never a one-size-fits-all package.')],
                    ['sparkle', __('Modern, Comfortable Environment'), __('A calm, bright clinic designed to help you relax, with careful attention to hygiene in every room.')],
                    ['plan', __('Clear Treatment Guidance'), __('We explain what we see, what your options are and what each step involves, so you can decide with confidence.')],
                    ['chat', __('A Patient-First Experience'), __('Time to ask questions, gentle care for nervous patients and follow-up that continues after you leave.')],
                ] as [$icon, $title, $text])
                    <li class="bg-ivory p-8 lg:p-10" data-reveal>
                        <x-site.icon :name="$icon" class="size-9 text-wine" />
                        <h3 class="mt-8 text-2xl leading-snug">{{ $title }}</h3>
                        <p class="mt-3 text-[0.9375rem] leading-relaxed text-ink-muted">{{ $text }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- 7. Inside our clinic --}}
    <section aria-labelledby="inside-heading" class="pb-20 md:pb-28">
        <div class="site-container">
            <div class="flex flex-col justify-between gap-8 md:flex-row md:items-end">
                <x-site.heading :eyebrow="__('Inside our clinic')" :title="__('Step inside before you visit')" id="inside-heading">
                    {{ __('Soft light, green plants and quiet rooms. A space designed to put you at ease from the moment you arrive.') }}
                </x-site.heading>
                <a href="{{ route('public.about') }}" class="btn btn-outline shrink-0 self-start md:self-auto">{{ __('View Our Clinic') }}</a>
            </div>
            <div class="mt-12 grid gap-5 md:grid-cols-12 md:grid-rows-2">
                <figure class="md:col-span-5 md:row-span-2" data-reveal>
                    <x-site.photo name="reception" sizes="(min-width: 768px) 40vw, 100vw" class="aspect-[4/5] size-full rounded-[2rem] object-cover md:aspect-auto" />
                </figure>
                <figure class="md:col-span-7" data-reveal>
                    <x-site.photo name="treatment-room" sizes="(min-width: 768px) 55vw, 100vw" class="aspect-[16/10] size-full rounded-[2rem] object-cover" />
                </figure>
                <div class="grid gap-5 sm:grid-cols-2 md:col-span-7">
                    <figure data-reveal>
                        <x-site.photo name="doctor" sizes="(min-width: 768px) 28vw, 100vw" class="aspect-[4/3] size-full rounded-[2rem] object-cover object-[58%_40%]" />
                    </figure>
                    <div class="flex flex-col justify-between rounded-[2rem] bg-blush p-8" data-reveal>
                        <x-site.icon name="pin" class="size-8 text-wine" />
                        <div>
                            <p class="font-display text-2xl leading-snug">{{ $address ?: __('Address to be confirmed') }}</p>
                            <a href="{{ route('public.contact') }}" class="link-arrow mt-4 text-sm">{{ __('Plan your visit') }} <x-site.icon name="arrow" class="arrow size-4" /></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 8. Testimonials --}}
    <section aria-labelledby="testimonials-heading" class="bg-cream py-20 md:py-28" data-carousel>
        <div class="site-container">
            <div class="flex flex-col justify-between gap-8 md:flex-row md:items-end">
                <x-site.heading :eyebrow="__('Patient stories')" :title="__('Kind words from our patients')" id="testimonials-heading" />
                <div class="flex gap-3">
                    <button type="button" data-carousel-prev class="flex size-12 items-center justify-center rounded-full border border-wine/30 text-wine transition hover:bg-wine hover:text-ivory">
                        <x-site.icon name="arrow" class="size-5 -scale-x-100 rtl:scale-x-100" /><span class="sr-only">{{ __('Previous') }}</span>
                    </button>
                    <button type="button" data-carousel-next class="flex size-12 items-center justify-center rounded-full border border-wine/30 text-wine transition hover:bg-wine hover:text-ivory">
                        <x-site.icon name="arrow" class="size-5 rtl:-scale-x-100" /><span class="sr-only">{{ __('Next') }}</span>
                    </button>
                </div>
            </div>
            @if ($testimonials->contains('sample', true))
                <p class="mt-6"><span class="sample-badge">{{ __('Sample testimonials for design only, not real reviews') }}</span></p>
            @endif
            <ul data-carousel-track class="-mx-5 mt-12 flex snap-x snap-mandatory gap-6 overflow-x-auto px-5 pb-4 [scrollbar-width:none] md:mx-0 md:px-0" aria-label="{{ __('Testimonials') }}">
                @foreach ($testimonials as $testimonial)
                    <li class="w-[85%] shrink-0 snap-start sm:w-[60%] lg:w-[calc((100%-3rem)/3)]">
                        <figure class="flex h-full flex-col rounded-[2rem] bg-ivory p-8 md:p-10">
                            <x-site.icon name="quote" class="size-9 text-wine/70" />
                            <blockquote class="mt-6 flex-1 font-display text-[1.4rem] leading-snug">{{ $testimonial['body'] }}</blockquote>
                            <figcaption class="mt-8 flex items-center justify-between gap-3 border-t border-line pt-6 text-sm">
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

    {{-- 9. Blog --}}
    <section aria-labelledby="blog-heading" class="py-20 md:py-28">
        <div class="site-container">
            <div class="flex flex-col justify-between gap-8 md:flex-row md:items-end">
                <x-site.heading :eyebrow="__('Dental advice')" :title="__('From the blog')" id="blog-heading">
                    {{ __('Practical, easy-to-read guidance to help you look after your teeth between visits.') }}
                </x-site.heading>
                <a href="{{ route('public.blog.index') }}" class="btn btn-outline shrink-0 self-start md:self-auto">{{ __('All articles') }}</a>
            </div>
            <ul class="mt-12 grid gap-10 md:grid-cols-3 md:gap-8">
                @foreach ($posts as $post)
                    <li data-reveal><x-site.post-card :post="$post" /></li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- 10. Appointment call to action --}}
    <x-site.cta class="pt-0 md:pt-0" />
</x-layouts::public>
