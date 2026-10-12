<x-layouts::public :title="null" :description="__('Dental care in :clinic. Request an appointment online.', ['clinic' => config('clinic.name')])" :schema="$schema">
    <section aria-labelledby="home-heading" class="max-w-2xl">
        <h1 id="home-heading" class="text-3xl font-semibold md:text-4xl">{{ config('clinic.name') }}</h1>
        <p class="mt-4 text-lg text-ink-muted">{{ __('Dental care for the whole family.') }}</p>

        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('public.booking') }}" class="rounded-md bg-brand px-5 py-2.5 font-medium text-brand-contrast">
                {{ __('Request an appointment') }}
            </a>
            <a href="{{ route('public.services.index') }}" class="rounded-md border border-line px-5 py-2.5 font-medium">
                {{ __('Our services') }}
            </a>
        </div>
    </section>

    @if ($services->isNotEmpty())
        <x-public.section id="home-services" :title="__('Our services')" :link="route('public.services.index')" :link-label="__('All services')">
            <ul class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($services as $service)
                    <li><x-public.service-card :service="$service" /></li>
                @endforeach
            </ul>
        </x-public.section>
    @endif

    @if ($dentists->isNotEmpty())
        <x-public.section id="home-dentists" :title="__('Meet the dentists')" :link="route('public.dentists.index')" :link-label="__('All dentists')">
            <ul class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($dentists as $profile)
                    <li><x-public.dentist-card :profile="$profile" /></li>
                @endforeach
            </ul>
        </x-public.section>
    @endif

    @if ($testimonials->isNotEmpty())
        <x-public.section id="home-testimonials" :title="__('What our patients say')" :link="route('public.testimonials')" :link-label="__('More testimonials')">
            <ul class="grid gap-5 md:grid-cols-3">
                @foreach ($testimonials as $testimonial)
                    <li>
                        <figure class="h-full rounded-lg border border-line p-5">
                            <blockquote class="text-sm">{{ $testimonial->body }}</blockquote>
                            <figcaption class="mt-3 text-sm font-medium">— {{ $testimonial->author_name }}</figcaption>
                        </figure>
                    </li>
                @endforeach
            </ul>
        </x-public.section>
    @endif

    @if ($posts->isNotEmpty())
        <x-public.section id="home-blog" :title="__('From the blog')" :link="route('public.blog.index')" :link-label="__('All articles')">
            <ul class="grid gap-5 md:grid-cols-3">
                @foreach ($posts as $post)
                    <li><x-public.post-card :post="$post" /></li>
                @endforeach
            </ul>
        </x-public.section>
    @endif
</x-layouts::public>
