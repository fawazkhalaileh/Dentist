<x-layouts::public :title="$treatment['title']" :description="$treatment['summary']" :bleed="true" :noindex="true">
    <x-site.treatment-detail :title="$treatment['title']" :summary="$treatment['summary']" :icon="$treatment['icon']" :sample="true" :related="$related">
        <section aria-labelledby="intro-heading">
            <h2 id="intro-heading" class="text-4xl">{{ __('About this treatment') }}</h2>
            <p class="mt-5 text-lg leading-relaxed text-ink-muted">{{ $treatment['intro'] }}</p>
        </section>

        <section aria-labelledby="suitable-heading">
            <h2 id="suitable-heading" class="text-4xl">{{ __('Who it may suit') }}</h2>
            <ul class="mt-6 grid gap-4 sm:grid-cols-3">
                @foreach ($treatment['suitable'] as $item)
                    <li class="rounded-2xl border border-line bg-white/60 p-5">
                        <x-site.icon name="heart" class="size-6 text-wine" />
                        <p class="mt-3 leading-snug">{{ $item }}</p>
                    </li>
                @endforeach
            </ul>
        </section>

        <section aria-labelledby="steps-heading">
            <h2 id="steps-heading" class="text-4xl">{{ __('What is involved') }}</h2>
            <ol class="mt-6 space-y-4">
                @foreach ($treatment['steps'] as $i => $step)
                    <li class="flex items-start gap-5 rounded-2xl bg-cream p-5">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-wine font-display text-lg text-ivory">{{ $i + 1 }}</span>
                        <p class="pt-1.5 text-lg">{{ $step }}</p>
                    </li>
                @endforeach
            </ol>
        </section>

        <section aria-labelledby="faq-heading">
            <h2 id="faq-heading" class="text-4xl">{{ __('Frequently asked questions') }}</h2>
            <x-site.faq-list :items="$treatment['faqs']" class="mt-6" />
        </section>
    </x-site.treatment-detail>

    <x-site.cta />
</x-layouts::public>
