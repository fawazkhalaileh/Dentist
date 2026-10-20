@php
    $doctor = \App\Support\Site\SiteContent::doctorName();
    $dentists = \App\Models\StaffProfile::shownPublicly()->translatedIn()->with(['translations', 'photo'])->orderBy('sort_order')->get();
@endphp

<x-layouts::public :title="__('Meet :name', ['name' => $doctor])" :bleed="true"
    :description="__('Meet :name, the dentist at :clinic in Amman, and learn about the personal approach to your care.', ['name' => $doctor, 'clinic' => \App\Support\ClinicDetails::name()])"
    :image="asset('images/site/doctor-1280.webp')">

    <section aria-labelledby="doctor-heading" class="relative overflow-hidden">
        <div aria-hidden="true" class="absolute inset-x-0 top-0 h-[70%] bg-cream"></div>
        <div class="site-container relative pt-10 md:pt-14">
            <x-site.breadcrumbs :items="[[__('Home'), route('public.home')], [__('Meet :name', ['name' => $doctor]), null]]" />
            <div class="mt-10 grid items-end gap-12 lg:grid-cols-[1.1fr_1fr] lg:gap-20">
                <div class="pb-4 lg:pb-16">
                    <p class="eyebrow">{{ __('Meet your dentist') }}</p>
                    <h1 id="doctor-heading" class="mt-6 text-5xl leading-none sm:text-6xl lg:text-7xl xl:text-8xl">{{ $doctor }}</h1>
                    <p class="mt-8 max-w-xl text-xl leading-relaxed text-ink-muted">
                        {{ __('Every smile is different, and so is every patient. :name believes good dentistry starts with a proper conversation: understanding how you feel, what you hope for and what worries you.', ['name' => $doctor]) }}
                    </p>
                    <div class="mt-10 flex flex-wrap items-center gap-4">
                        <a href="{{ route('public.booking') }}" class="btn btn-primary">{{ __('Book an Appointment') }}</a>
                        <span class="sample-badge">{{ __('Placeholder biography') }}</span>
                    </div>
                </div>
                <x-site.photo name="doctor" :eager="true" sizes="(min-width: 1024px) 45vw, 100vw" class="aspect-[4/5] w-full rounded-t-[14rem] rounded-b-[2rem] object-cover object-[58%_40%] shadow-[0_40px_80px_-40px_rgb(34_27_29/0.5)]" />
            </div>
        </div>
    </section>

    <section aria-labelledby="approach-heading" class="py-20 md:py-28">
        <div class="site-container grid gap-14 lg:grid-cols-[1fr_1.4fr] lg:gap-24">
            <div>
                <x-site.heading :eyebrow="__('Philosophy')" :title="__('A gentle, honest approach')" id="approach-heading" />
                <blockquote class="mt-10 border-s-2 border-wine ps-6 font-display text-3xl italic leading-snug">
                    “{{ __('My goal is simple: that you leave every visit feeling comfortable, informed and confident about your smile.') }}”
                </blockquote>
            </div>
            <div class="space-y-6 text-lg leading-relaxed text-ink-muted">
                <p>{{ __('This biography is placeholder text, ready to be replaced with words from the doctor. It will describe how the doctor came to dentistry, the kind of care they enjoy most and what patients can expect at their first visit.') }}</p>
                <p>{{ __('At every appointment, the focus is on comfort and clarity: explaining what is happening, checking in along the way and never rushing a decision about treatment.') }}</p>
                <ul class="grid gap-4 pt-4 sm:grid-cols-2">
                    @foreach ([['chat', __('Listens first, then advises')], ['plan', __('Explains every option clearly')], ['heart', __('Gentle with nervous patients')], ['sparkle', __('Attention to natural-looking detail')]] as [$icon, $text])
                        <li class="flex items-center gap-3 rounded-2xl border border-line bg-white/60 p-4 text-base text-ink">
                            <x-site.icon :name="$icon" class="size-6 shrink-0 text-wine" />{{ $text }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    <section aria-labelledby="background-heading" class="bg-cream py-20 md:py-28">
        <div class="site-container grid gap-6 md:grid-cols-2">
            <div class="rounded-[2rem] bg-ivory p-8 md:p-12">
                <h2 id="background-heading" class="text-3xl">{{ __('Professional background') }}</h2>
                <p class="mt-4 text-ink-muted">{{ __('Education, training and experience will be listed here once supplied and verified by the clinic.') }}</p>
                <p class="mt-6"><span class="sample-badge">{{ __('Awaiting verified information') }}</span></p>
            </div>
            <div class="rounded-[2rem] bg-ivory p-8 md:p-12">
                <h2 class="text-3xl">{{ __('Qualifications & memberships') }}</h2>
                <p class="mt-4 text-ink-muted">{{ __('Degrees, licences and professional memberships will appear here after verification. We never list credentials we have not confirmed.') }}</p>
                <p class="mt-6"><span class="sample-badge">{{ __('Awaiting verified information') }}</span></p>
            </div>
        </div>
    </section>

    @if ($dentists->isNotEmpty())
        <section aria-labelledby="team-heading" class="py-20 md:py-28">
            <div class="site-container">
                <x-site.heading :eyebrow="__('Our team')" :title="__('Meet the dentists')" id="team-heading" />
                <ul class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($dentists as $profile)
                        <li><x-public.dentist-card :profile="$profile" /></li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    <x-site.cta />
</x-layouts::public>
