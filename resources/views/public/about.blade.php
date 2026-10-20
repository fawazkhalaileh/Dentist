@php
    $doctor = \App\Support\Site\SiteContent::doctorName();
    $clinic = \App\Support\ClinicDetails::name();
@endphp

<x-layouts::public :title="$translation?->seo_title ?: ($translation?->title ?? __('About Us'))" :bleed="true"
    :description="$translation?->meta_description ?: __('About :clinic: a calm, modern dental clinic in Amman focused on personal, unhurried care.', ['clinic' => $clinic])">

    <x-site.page-hero :title="$translation?->title ?? __('About Us')" :eyebrow="__('Our clinic')" photo="reception"
        :intro="__('A calm, modern clinic in Amman where every visit starts with listening.')" />

    <section aria-labelledby="story-heading" class="py-20 md:py-28">
        <div class="site-container grid gap-14 lg:grid-cols-[1fr_1.3fr] lg:gap-24">
            <div>
                <x-site.heading :eyebrow="__('Our story')" :title="__('Care that feels personal')" id="story-heading" />
                <figure class="mt-10 hidden lg:block">
                    <x-site.photo name="treatment-room" sizes="35vw" class="aspect-[4/5] w-full rounded-[2rem] object-cover" />
                </figure>
            </div>
            <div>
                @if ($translation?->body)
                    <x-ui.prose :markdown="$translation->body" />
                @else
                    <div class="space-y-6 text-lg leading-relaxed text-ink-muted">
                        <p>{{ __('We believe a visit to the dentist should feel calm and clear. That is why we take time to get to know each patient, explain what we see and plan treatment together.') }}</p>
                        <p>{{ __('Our clinic was designed to feel welcoming: soft light, plants and quiet treatment rooms, with careful attention to hygiene in every corner.') }}</p>
                        <p>{{ __('Whether you need a routine check-up or are planning a bigger change to your smile, you will always know your options and what to expect.') }}</p>
                    </div>
                    <p class="mt-8"><span class="sample-badge">{{ __('Placeholder copy, awaiting approval') }}</span></p>
                @endif
            </div>
        </div>
    </section>

    <section aria-labelledby="values-heading" class="bg-cream py-20 md:py-28">
        <div class="site-container">
            <x-site.heading :eyebrow="__('What we stand for')" :title="__('Our values')" id="values-heading" align="center" />
            <ul class="mt-14 grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['chat', __('Honest advice'), __('We recommend only what you need, explain why, and respect your decision.')],
                    ['heart', __('Gentle care'), __('Unhurried appointments, clear communication and extra patience for nervous patients.')],
                    ['shield', __('High standards'), __('Careful planning, modern equipment and strict hygiene at every step.')],
                ] as [$icon, $title, $text])
                    <li class="rounded-[2rem] bg-ivory p-8 md:p-10" data-reveal>
                        <span class="flex size-14 items-center justify-center rounded-full bg-blush text-wine"><x-site.icon :name="$icon" class="size-7" /></span>
                        <h3 class="mt-8 text-3xl">{{ $title }}</h3>
                        <p class="mt-3 leading-relaxed text-ink-muted">{{ $text }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    <section aria-labelledby="space-heading" class="py-20 md:py-28">
        <div class="site-container">
            <x-site.heading :eyebrow="__('Inside our clinic')" :title="__('A space designed to put you at ease')" id="space-heading" />
            <div class="mt-12 grid gap-5 md:grid-cols-3">
                <x-site.photo name="reception" sizes="(min-width: 768px) 33vw, 100vw" class="aspect-[3/4] w-full rounded-[2rem] object-cover" data-reveal />
                <x-site.photo name="treatment-room" sizes="(min-width: 768px) 33vw, 100vw" class="aspect-[3/4] w-full rounded-[2rem] object-cover" data-reveal />
                <x-site.photo name="doctor" sizes="(min-width: 768px) 33vw, 100vw" class="aspect-[3/4] w-full rounded-[2rem] object-cover object-[58%_40%]" data-reveal />
            </div>
        </div>
    </section>

    <section aria-labelledby="team-heading" class="pb-6">
        <div class="site-container">
            <div class="grid overflow-hidden rounded-[2rem] bg-night text-ivory md:grid-cols-2">
                <x-site.photo name="doctor" sizes="(min-width: 768px) 50vw, 100vw" class="aspect-[4/3] size-full object-cover object-[58%_40%]" />
                <div class="flex flex-col justify-center p-8 md:p-14">
                    <p class="eyebrow !text-ivory/70">{{ __('Meet your dentist') }}</p>
                    <h2 id="team-heading" class="mt-5 text-4xl md:text-5xl">{{ $doctor }}</h2>
                    <p class="mt-5 leading-relaxed text-ivory/75">{{ __('Get to know the person behind your care before your first appointment.') }}</p>
                    <a href="{{ route('public.doctor') }}" class="btn btn-light mt-8 self-start">{{ __('Meet the Doctor') }}</a>
                </div>
            </div>
        </div>
    </section>

    <x-site.cta />
</x-layouts::public>
