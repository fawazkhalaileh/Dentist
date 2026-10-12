<x-layouts::public :title="null" :description="__('Dental care and appointment requests.')">
    <section aria-labelledby="home-heading" class="max-w-2xl">
        <h1 id="home-heading" class="text-3xl font-semibold">{{ config('clinic.name') }}</h1>
        <p class="mt-4 text-ink-muted">
            {{ __('Website content is added in Phase 2. This page shows the public layout, navigation and language switching.') }}
        </p>

        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('public.booking') }}" class="rounded-md bg-brand px-4 py-2 font-medium text-brand-contrast">
                {{ __('Request an appointment') }}
            </a>
            <a href="{{ route('public.services.index') }}" class="rounded-md border border-line px-4 py-2 font-medium">
                {{ __('Our services') }}
            </a>
        </div>
    </section>
</x-layouts::public>
