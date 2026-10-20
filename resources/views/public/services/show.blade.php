<x-layouts::public
    :title="$translation->seo_title ?: $translation->title"
    :description="$translation->meta_description ?: $translation->summary"
    :alternates="$alternates"
    :image="$service->featuredMedia?->url()"
    :schema="$schema"
    :bleed="true"
>
    <x-site.treatment-detail :title="$translation->title" :summary="$translation->summary" :media="$service->featuredMedia" :related="$related"
        :booking-url="$service->is_bookable ? route('public.booking', ['service' => $service->id]) : null">
        @foreach ($sections as $section)
            <section>
                <h2 class="text-4xl">{{ $section->heading }}</h2>
                <x-ui.prose :markdown="$section->body" class="mt-5" />
            </section>
        @endforeach

        @if ($translation->procedure_information)
            <section>
                <h2 class="text-4xl">{{ __('About the procedure') }}</h2>
                <x-ui.prose :markdown="$translation->procedure_information" class="mt-5" />
            </section>
        @endif

        @if ($sections->isEmpty() && ! $translation->procedure_information)
            <p class="text-lg leading-relaxed text-ink-muted">{{ __('More information about this treatment is coming soon. In the meantime, our team is happy to answer your questions.') }}</p>
        @endif

        @if ($faqs->isNotEmpty())
            <section aria-labelledby="service-faq">
                <h2 id="service-faq" class="text-4xl">{{ __('Frequently asked questions') }}</h2>
                <x-site.faq-list :items="$faqs->map(fn ($f) => [$f->question, $f->answer])->all()" :markdown="true" class="mt-6" />
            </section>
        @endif

        @if ($dentists->isNotEmpty())
            <section aria-labelledby="service-dentists">
                <h2 id="service-dentists" class="text-4xl">{{ __('Dentists who provide this service') }}</h2>
                <ul class="mt-6 grid gap-5 sm:grid-cols-2">
                    @foreach ($dentists as $profile)
                        <li><x-public.dentist-card :profile="$profile" /></li>
                    @endforeach
                </ul>
            </section>
        @endif
    </x-site.treatment-detail>

    <x-site.cta />
</x-layouts::public>
