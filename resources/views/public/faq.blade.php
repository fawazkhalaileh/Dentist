<x-layouts::public :title="__('Frequently asked questions')" :bleed="true" :description="__('Answers to common questions about visiting :clinic.', ['clinic' => \App\Support\ClinicDetails::name()])" :schema="$schema">
    <x-site.page-hero :title="__('Frequently asked questions')" :eyebrow="__('Good to know')" />

    <div class="site-container max-w-4xl py-10 md:py-16">
        @if ($faqs->isEmpty())
            <x-ui.empty-state :title="__('No questions published yet')" :description="__('Please contact the clinic with any question.')" />
        @else
            <x-site.faq-list :items="$faqs->map(fn ($f) => [$f->question, $f->answer])->all()" :markdown="true" />
        @endif
        <p class="mt-12 text-ink-muted">{{ __('Can’t find your answer?') }} <a href="{{ route('public.contact') }}" class="text-wine underline underline-offset-4">{{ __('Contact us') }}</a></p>
    </div>
</x-layouts::public>
