<x-layouts::public :title="null" :description="__('Dental care in :clinic. Request an appointment online.', ['clinic' => \App\Support\ClinicDetails::name()])" :schema="$schema">
    @php
        $phone = \App\Support\ClinicDetails::get('phone');
        $whatsapp = \App\Support\ClinicDetails::get('whatsapp');
        $hours = \App\Support\ClinicDetails::get('opening_hours');
        $address = \App\Support\ClinicDetails::get('address');
    @endphp
    <section aria-labelledby="home-heading" class="grid gap-8 rounded-2xl bg-brand-soft p-6 md:grid-cols-[3fr_2fr] md:p-12">
        <div>
            <h1 id="home-heading" class="text-3xl font-semibold md:text-5xl">{{ \App\Support\ClinicDetails::name() }}</h1>
            <p class="mt-4 text-lg text-ink-muted">{{ __('Dental care for the whole family.') }}</p>
            <p class="mt-2 text-ink-muted">{{ __('Request an appointment online and we will call you back to agree a time.') }}</p>

            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('public.booking') }}" class="rounded-lg bg-brand px-5 py-3 font-medium text-brand-contrast shadow-sm hover:opacity-90">
                    {{ __('Request an appointment') }}
                </a>
                <a href="{{ route('public.services.index') }}" class="rounded-lg border border-line bg-surface px-5 py-3 font-medium hover:border-brand">
                    {{ __('Our services') }}
                </a>
            </div>
        </div>

        @if ($phone || $hours || $address)
            <aside class="self-start rounded-xl bg-surface p-6 shadow-sm" aria-label="{{ __('Visit us') }}">
                <h2 class="font-semibold">{{ __('Visit us') }}</h2>
                <dl class="mt-4 space-y-4 text-sm">
                    @if ($phone)
                        <div>
                            <dt class="text-ink-muted">{{ __('Call us') }}</dt>
                            <dd><a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" class="text-lg font-semibold text-brand" dir="ltr">{{ $phone }}</a>
                                @if ($whatsapp) <a href="https://wa.me/{{ preg_replace('/\D/', '', $whatsapp) }}" class="ms-2 text-sm underline" rel="noopener">WhatsApp</a> @endif
                            </dd>
                        </div>
                    @endif
                    @if ($hours)
                        <div><dt class="text-ink-muted">{{ __('Opening hours') }}</dt><dd class="whitespace-pre-line">{{ $hours }}</dd></div>
                    @endif
                    @if ($address)
                        <div><dt class="text-ink-muted">{{ __('Address') }}</dt><dd class="whitespace-pre-line">{{ $address }}</dd></div>
                    @endif
                </dl>
            </aside>
        @endif
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
