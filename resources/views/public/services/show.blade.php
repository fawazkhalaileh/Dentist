<x-layouts::public
    :title="$translation->seo_title ?: $translation->title"
    :description="$translation->meta_description ?: $translation->summary"
    :alternates="$alternates"
    :image="$service->featuredMedia?->url()"
    :schema="$schema"
>
    <nav aria-label="{{ __('Breadcrumb') }}" class="mb-4 text-sm text-ink-muted">
        <a href="{{ route('public.services.index') }}" class="hover:underline">{{ __('Our services') }}</a>
        <span aria-hidden="true"> / </span>
        <span aria-current="page">{{ $translation->title }}</span>
    </nav>

    <article>
        <header class="max-w-3xl">
            <h1 class="text-3xl font-semibold">{{ $translation->title }}</h1>
            @if ($translation->summary)
                <p class="mt-3 text-lg text-ink-muted">{{ $translation->summary }}</p>
            @endif
        </header>

        @if ($service->featuredMedia)
            <x-ui.picture :media="$service->featuredMedia" :alt="$translation->title" eager sizes="(min-width: 1024px) 960px, 100vw" class="mt-6 w-full max-w-4xl rounded-lg" />
        @endif

        <div class="mt-8 max-w-3xl space-y-8">
            @foreach ($sections as $section)
                <section>
                    <h2 class="text-xl font-semibold">{{ $section->heading }}</h2>
                    <x-ui.prose :markdown="$section->body" class="mt-2" />
                </section>
            @endforeach

            @if ($translation->procedure_information)
                <section>
                    <h2 class="text-xl font-semibold">{{ __('About the procedure') }}</h2>
                    <x-ui.prose :markdown="$translation->procedure_information" class="mt-2" />
                </section>
            @endif

            @if ($faqs->isNotEmpty())
                <section aria-labelledby="service-faq">
                    <h2 id="service-faq" class="text-xl font-semibold">{{ __('Frequently asked questions') }}</h2>
                    <div class="mt-3 divide-y divide-line rounded-lg border border-line">
                        @foreach ($faqs as $faq)
                            <details class="group p-4">
                                <summary class="cursor-pointer font-medium">{{ $faq->question }}</summary>
                                <x-ui.prose :markdown="$faq->answer" class="mt-2" />
                            </details>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        @if ($dentists->isNotEmpty())
            <x-public.section id="service-dentists" :title="__('Dentists who provide this service')">
                <ul class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($dentists as $profile)
                        <li><x-public.dentist-card :profile="$profile" /></li>
                    @endforeach
                </ul>
            </x-public.section>
        @endif

        @if ($service->is_bookable)
            <div class="mt-12">
                <a href="{{ route('public.booking', ['service' => $service->id]) }}" class="rounded-md bg-brand px-5 py-2.5 font-medium text-brand-contrast">
                    {{ __('Request an appointment') }}
                </a>
            </div>
        @endif
    </article>
</x-layouts::public>
