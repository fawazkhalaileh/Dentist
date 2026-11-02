@php
    $doctor = \App\Support\Site\SiteContent::doctorName();
    $dentists = \App\Models\StaffProfile::shownPublicly()->translatedIn()->with(['translations', 'photo'])->orderBy('sort_order')->get();
@endphp

<x-layouts::public :title="__('Meet :name', ['name' => $doctor])" :bleed="true"
    :description="__('Meet :name, the dentist at :clinic in Amman, and learn about the personal approach to your care.', ['name' => $doctor, 'clinic' => \App\Support\ClinicDetails::name()])"
    :image="asset('images/site/doctor-1280.webp')" :schema="\App\Support\Seo::doctor()">

    <section aria-labelledby="doctor-heading" class="relative overflow-hidden">
        <div aria-hidden="true" class="absolute inset-x-0 top-0 h-[70%] bg-cream"></div>
        <div class="site-container relative pt-10 md:pt-14">
            <x-site.breadcrumbs :items="[[__('Home'), route('public.home')], [__('Meet :name', ['name' => $doctor]), null]]" />
            <div class="mt-10 grid items-end gap-12 lg:grid-cols-[1.1fr_1fr] lg:gap-20">
                <div class="pb-4 lg:pb-16">
                    <p class="eyebrow">{{ __('Meet your dentist') }}</p>
                    <h1 id="doctor-heading" class="mt-6 text-5xl leading-none sm:text-6xl lg:text-7xl xl:text-8xl">{{ $doctor }}</h1>
                    <p class="mt-6 text-xl text-wine">{{ __('Precision. Artistry. Excellence in Dentistry.') }}</p>
                    <p class="mt-8 max-w-xl text-xl leading-relaxed text-ink-muted">
                        {{ __('Dr. Mohammad Amara is the founder of Amara Dental, a contemporary dental practice in the heart of Amman, Jordan. A graduate of Jordan University of Science and Technology, he holds a Bachelor of Dental Surgery (BDS), complemented by ADA-certified advanced training in Implantology through ESOI and Advanced Endodontics.') }}
                    </p>
                    <div class="mt-10 flex flex-wrap items-center gap-4">
                        <a href="{{ route('public.booking') }}" class="btn btn-primary">{{ __('Book an Appointment') }}</a>
                    </div>
                </div>
                <x-site.photo name="doctor" :eager="true" sizes="(min-width: 1024px) 45vw, 100vw" class="aspect-[4/5] w-full rounded-t-[14rem] rounded-b-[2rem] object-cover object-[58%_40%] shadow-[0_40px_80px_-40px_rgb(34_27_29/0.5)]" />
            </div>
        </div>
    </section>

    <section aria-labelledby="approach-heading" class="py-12 md:py-20">
        <div class="site-container grid gap-14 lg:grid-cols-[1fr_1.4fr] lg:gap-24">
            <div>
                <x-site.heading :eyebrow="__('Philosophy')" :title="__('A gentle, honest approach')" id="approach-heading" />
            </div>
            <div class="space-y-6 text-lg leading-relaxed text-ink-muted">
                <p>{{ __('With a particular passion for dental implantology, aesthetic and restorative dentistry, and comprehensive full-mouth rehabilitation, Dr. Amara brings together clinical precision, artistic vision, and modern dental technology to create smiles that are as functional as they are naturally beautiful.') }}</p>
                <p>{{ __('His philosophy is rooted in the belief that exceptional dentistry is never one-size-fits-all. Every treatment is thoughtfully planned around the individual, with meticulous attention to detail, facial harmony, and long-term oral health.') }}</p>
                <p>{{ __('At Amara Dental, Dr. Amara is dedicated to delivering a refined, personalized dental experience where advanced techniques meet genuine care, and every detail is considered.') }}</p>
            </div>
        </div>
    </section>

    <section aria-labelledby="background-heading" class="bg-cream py-12 md:py-20">
        <div class="site-container grid gap-6 md:grid-cols-2">
            <div class="rounded-[2rem] bg-ivory p-8 md:p-12">
                <h2 id="background-heading" class="text-3xl">{{ __('Education & Advanced Certifications') }}</h2>
                <ul class="mt-6 space-y-6 text-ink-muted">
                    <li>{{ __('Bachelor of Dental Surgery (BDS)') }} — {{ __('Jordan University of Science and Technology') }}</li>
                    <li>{{ __('Advanced Implantology') }} — {{ __('ESOI | ADA-Certified') }}</li>
                    <li>{{ __('Advanced Endodontics') }} — {{ __('ADA-Certified') }}</li>
                </ul>
            </div>
            <div class="rounded-[2rem] bg-ivory p-8 md:p-12">
                <h2 class="text-3xl">{{ __('Languages') }}</h2>
                <p class="mt-4 text-ink-muted">{{ __('Arabic & English') }}</p>
            </div>
        </div>
    </section>

    @if ($dentists->isNotEmpty())
        <section aria-labelledby="team-heading" class="py-12 md:py-20">
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
