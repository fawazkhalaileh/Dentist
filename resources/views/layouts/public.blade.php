@props([
    'title' => null,
    'description' => null,
    // [locale => url] of this page in each language it exists in. Defaults to the same route in every locale.
    'alternates' => null,
    'image' => null,
    'type' => 'website',
    // schema.org structured data (array), rendered as JSON-LD.
    'schema' => null,
    // Full-width page: sections manage their own containers (homepage and redesigned pages).
    'bleed' => false,
])

@use('App\Support\Locales')
@use('App\Support\ClinicDetails')

@php
    $locale = app()->getLocale();

    if ($alternates === null) {
        $route = request()->route();
        $routeName = $route?->getName();
        // Only real URI parameters; Route::view() also stores its view/data as route defaults.
        $routeParams = $route ? \Illuminate\Support\Arr::only($route->parameters(), $route->parameterNames()) : [];
        $isLocalized = is_string($routeName) && str_starts_with($routeName, 'public.');

        $alternates = [];
        foreach (Locales::supported() as $l) {
            $alternates[$l] = $isLocalized
                ? route($routeName, [...$routeParams, 'locale' => $l])
                : route('public.home', ['locale' => $l]);
        }
    }

    $canonical = $alternates[$locale] ?? url()->current();

    // The language switcher always offers every locale; missing translations fall back to that locale's home.
    $switcher = [];
    foreach (Locales::supported() as $l) {
        $switcher[$l] = $alternates[$l] ?? route('public.home', ['locale' => $l]);
    }

    $nav = [
        ['label' => 'Home', 'route' => 'public.home', 'active' => ['public.home']],
        ['label' => 'About Us', 'route' => 'public.about', 'active' => ['public.about']],
        ['label' => 'Meet :name', 'route' => 'public.doctor', 'active' => ['public.doctor', 'public.dentists.*']],
        ['label' => 'Treatments', 'route' => 'public.services.index', 'active' => ['public.services.*'], 'menu' => true],
        ['label' => 'Smile Gallery', 'route' => 'public.gallery', 'active' => ['public.gallery']],
        ['label' => 'Blog', 'route' => 'public.blog.index', 'active' => ['public.blog.*']],
        ['label' => 'Contact', 'route' => 'public.contact', 'active' => ['public.contact']],
    ];
    $doctor = \App\Support\Site\SiteContent::doctorShortName();
    $treatmentLinks = \App\Support\Site\SiteContent::treatments(8);

    $pageTitle = filled($title) ? $title.' | '.ClinicDetails::name() : ClinicDetails::name();
    $phone = ClinicDetails::get('phone');
    $whatsapp = ClinicDetails::get('whatsapp');
    $email = ClinicDetails::get('email');
    $address = ClinicDetails::get('address');
    $hours = ClinicDetails::get('opening_hours');
    $mapUrl = ClinicDetails::get('map_url') ?: ($address ? 'https://www.google.com/maps/search/?api=1&query='.urlencode($address) : null);
    $fonts = $locale === 'ar'
        ? ['ibm-plex-sans-arabic', 'noto-naskh-arabic', 'instrument-sans', 'cormorant-garamond']
        : ['instrument-sans', 'cormorant-garamond'];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ Locales::dir() }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>{{ $pageTitle }}</title>
        @if (filled($description))
            <meta name="description" content="{{ $description }}" />
        @endif
        <link rel="canonical" href="{{ $canonical }}" />
        @foreach ($alternates as $l => $url)
            <link rel="alternate" hreflang="{{ $l }}" href="{{ $url }}" />
        @endforeach
        <link rel="alternate" hreflang="x-default" href="{{ route('home') }}" />

        <meta property="og:site_name" content="{{ ClinicDetails::name() }}" />
        <meta property="og:title" content="{{ $title ?? ClinicDetails::name() }}" />
        <meta property="og:type" content="{{ $type }}" />
        <meta property="og:url" content="{{ $canonical }}" />
        <meta property="og:locale" content="{{ $locale }}" />
        @if (filled($description))
            <meta property="og:description" content="{{ $description }}" />
        @endif
        <meta property="og:image" content="{{ filled($image) ? $image : asset('images/site/reception-1200.webp') }}" />

        @if (! empty($schema))
            <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
        @endif

        <meta property="og:image:alt" content="{{ ClinicDetails::name() }}" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="theme-color" content="#7c0424" />

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts($fonts)
        @vite(['resources/css/app.css', 'resources/js/site.js'])
    </head>
    <body class="site flex min-h-screen flex-col bg-surface text-ink antialiased">
        <x-ui.skip-link />

        <header data-site-header class="site-header sticky top-0 z-40 border-b border-transparent bg-ivory/90 backdrop-blur-md transition-shadow">
            <div class="site-container flex h-[4.5rem] items-center justify-between gap-6 lg:h-20">
                <a href="{{ route('public.home') }}" class="flex h-10 shrink-0 items-center lg:h-11" aria-label="{{ __('Amara Dental, home') }}">
                    <x-site.logo class="h-full" />
                </a>

                <nav aria-label="{{ __('Main navigation') }}" class="hidden xl:block">
                    <ul class="flex items-center gap-1 text-[0.9375rem]">
                        @foreach ($nav as $item)
                            @php $isActive = request()->routeIs(...$item['active']); @endphp
                            <li class="relative">
                                @if ($item['menu'] ?? false)
                                    <button type="button" data-disclosure="menu" aria-expanded="false" aria-controls="treatments-menu"
                                            class="group inline-flex items-center gap-1 rounded-full px-3.5 py-2 transition hover:text-wine {{ $isActive ? 'text-wine font-semibold' : '' }}">
                                        {{ __($item['label']) }}
                                        <x-site.icon name="chevron-down" class="size-4 transition group-aria-expanded:rotate-180" />
                                    </button>
                                @else
                                    <a href="{{ route($item['route']) }}"
                                       @if ($isActive) aria-current="page" @endif
                                       class="inline-flex rounded-full px-3.5 py-2 transition hover:text-wine {{ $isActive ? 'text-wine font-semibold' : '' }}">{{ __($item['label'], ['name' => $doctor]) }}</a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </nav>

                <div class="flex items-center gap-2 sm:gap-4">
                    <x-ui.language-switcher :alternates="$switcher" class="hidden text-sm sm:flex" />
                    <a href="{{ route('public.booking') }}" class="btn btn-primary btn-sm">
                        <span class="sm:hidden">{{ __('Book') }}</span><span class="hidden sm:inline">{{ __('Book an Appointment') }}</span>
                    </a>
                    <button type="button" data-disclosure="modal" aria-expanded="false" aria-controls="mobile-menu"
                            class="inline-flex size-11 items-center justify-center rounded-full border border-line text-ink xl:hidden">
                        <x-site.icon name="menu" />
                        <span class="sr-only">{{ __('Menu') }}</span>
                    </button>
                </div>
            </div>

            {{-- Treatments menu (desktop) --}}
            <div id="treatments-menu" hidden class="absolute inset-x-0 top-full border-y border-line bg-ivory shadow-[0_30px_60px_-30px_rgb(34_27_29/0.35)]">
                <div class="site-container grid gap-10 py-10 lg:grid-cols-[2fr_1fr]">
                    <div>
                        <p class="eyebrow">{{ __('Treatments') }}</p>
                        <ul class="mt-6 grid gap-x-8 gap-y-2 sm:grid-cols-2">
                            @foreach ($treatmentLinks as $treatment)
                                <li>
                                    <a href="{{ $treatment['url'] }}" class="group flex items-start gap-4 rounded-2xl p-3 transition hover:bg-cream">
                                        <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-blush text-wine"><x-site.icon :name="$treatment['icon']" class="size-5" /></span>
                                        <span>
                                            <span class="block font-medium group-hover:text-wine">{{ $treatment['title'] }}</span>
                                            @if ($treatment['summary'])
                                                <span class="mt-0.5 line-clamp-1 block text-sm text-ink-muted">{{ $treatment['summary'] }}</span>
                                            @endif
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                        <a href="{{ route('public.services.index') }}" class="link-arrow mt-6 ms-3 text-sm">{{ __('View all treatments') }} <x-site.icon name="arrow" class="arrow size-4" /></a>
                    </div>
                    <div class="relative overflow-hidden rounded-3xl bg-night text-ivory">
                        <x-site.photo name="treatment-room" sizes="400px" alt="" class="absolute inset-0 size-full object-cover opacity-45" />
                        <div class="relative flex h-full flex-col justify-end p-7">
                            <p class="font-display text-2xl leading-tight">{{ __('Not sure where to start?') }}</p>
                            <p class="mt-2 text-sm text-ivory/80">{{ __('Book a consultation and we will talk through your options together.') }}</p>
                            <a href="{{ route('public.booking') }}" class="btn btn-light btn-sm mt-5 self-start">{{ __('Book an Appointment') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        {{-- Mobile menu --}}
        <div id="mobile-menu" hidden role="dialog" aria-modal="true" aria-label="{{ __('Menu') }}" class="fixed inset-0 z-50 overflow-y-auto bg-ivory xl:hidden">
            <div class="site-container flex h-[4.5rem] items-center justify-between">
                <x-site.logo class="h-10" />
                <button type="button" data-close-disclosure="mobile-menu" class="inline-flex size-11 items-center justify-center rounded-full border border-line">
                    <x-site.icon name="close" />
                    <span class="sr-only">{{ __('Close menu') }}</span>
                </button>
            </div>
            <nav aria-label="{{ __('Main navigation') }}" class="site-container pb-10 pt-4">
                <ul class="divide-y divide-line border-y border-line">
                    @foreach ($nav as $item)
                        @php $isActive = request()->routeIs(...$item['active']); @endphp
                        <li>
                            @if ($item['menu'] ?? false)
                                <button type="button" data-disclosure="inline" aria-expanded="false" aria-controls="mobile-treatments"
                                        class="group flex w-full items-center justify-between py-4 font-display text-2xl {{ $isActive ? 'text-wine' : '' }}">
                                    {{ __($item['label']) }}
                                    <x-site.icon name="chevron-down" class="size-5 transition group-aria-expanded:rotate-180" />
                                </button>
                                <ul id="mobile-treatments" hidden class="grid gap-1 pb-4">
                                    @foreach ($treatmentLinks as $treatment)
                                        <li><a href="{{ $treatment['url'] }}" class="flex items-center gap-3 rounded-xl px-2 py-2 text-ink-muted hover:bg-cream hover:text-wine"><x-site.icon :name="$treatment['icon']" class="size-5 text-wine" />{{ $treatment['title'] }}</a></li>
                                    @endforeach
                                    <li><a href="{{ route('public.services.index') }}" class="link-arrow px-2 py-2 text-sm">{{ __('View all treatments') }} <x-site.icon name="arrow" class="arrow size-4" /></a></li>
                                </ul>
                            @else
                                <a href="{{ route($item['route']) }}" @if ($isActive) aria-current="page" @endif
                                   class="block py-4 font-display text-2xl {{ $isActive ? 'text-wine' : '' }}">{{ __($item['label'], ['name' => $doctor]) }}</a>
                            @endif
                        </li>
                    @endforeach
                </ul>

                <div class="mt-8 grid gap-3">
                    <a href="{{ route('public.booking') }}" class="btn btn-primary w-full">{{ __('Book an Appointment') }}</a>
                    @if ($phone)
                        <a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" class="btn btn-outline w-full"><x-site.icon name="phone" class="size-5" /> {{ __('Call us') }}</a>
                    @endif
                    @if ($whatsapp)
                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $whatsapp) }}" rel="noopener" class="btn btn-outline w-full"><x-site.icon name="whatsapp" class="size-5" /> WhatsApp</a>
                    @endif
                    @unless ($phone || $whatsapp)
                        <a href="{{ route('public.contact') }}" class="btn btn-outline w-full"><x-site.icon name="chat" class="size-5" /> {{ __('Contact us') }}</a>
                    @endunless
                </div>

                <div class="mt-8 flex items-center justify-between text-sm text-ink-muted">
                    <x-ui.language-switcher :alternates="$switcher" />
                    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="hover:text-wine">{{ auth()->check() ? __('My account') : __('Log in') }}</a>
                </div>
            </nav>
        </div>

        <main id="main-content" tabindex="-1" @class(['flex-1 focus:outline-none', 'site-container py-12 md:py-16' => ! $bleed])>
            {{ $slot }}
        </main>

        <footer class="bg-night text-ivory/75">
            <div class="site-container grid gap-12 py-16 md:grid-cols-2 lg:grid-cols-[1.3fr_1fr_1fr_1.3fr] lg:py-20">
                <div>
                    <x-site.logo variant="light" layout="stacked" class="w-28" />
                    <p class="mt-6 max-w-xs text-sm leading-relaxed">{{ __('Calm, considered dental care in Amman, for healthy, confident smiles.') }}</p>
                    <ul class="mt-6 flex gap-3" aria-label="{{ __('Social media') }}">
                        @foreach (config('site.social') as $network => $url)
                            <li>
                                <a href="{{ $url }}" rel="noopener" target="_blank" class="flex size-10 items-center justify-center rounded-full border border-ivory/20 transition hover:border-ivory hover:text-ivory">
                                    <x-site.icon :name="$network" class="size-5" />
                                    <span class="sr-only">{{ ucfirst($network) }} ({{ __('opens in a new tab') }})</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <nav aria-label="{{ __('Footer navigation') }}">
                    <p class="font-display text-xl text-ivory">{{ __('Explore') }}</p>
                    <ul class="mt-5 space-y-3 text-sm">
                        @foreach ($nav as $item)
                            <li><a href="{{ route($item['route']) }}" class="hover:text-ivory">{{ __($item['label'], ['name' => $doctor]) }}</a></li>
                        @endforeach
                        <li><a href="{{ route('public.faq') }}" class="hover:text-ivory">{{ __('FAQ') }}</a></li>
                    </ul>
                </nav>

                <div>
                    <p class="font-display text-xl text-ivory">{{ __('Treatments') }}</p>
                    <ul class="mt-5 space-y-3 text-sm">
                        @foreach ($treatmentLinks->take(6) as $treatment)
                            <li><a href="{{ $treatment['url'] }}" class="hover:text-ivory">{{ $treatment['title'] }}</a></li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <p class="font-display text-xl text-ivory">{{ __('Visit us') }}</p>
                    <ul class="mt-5 space-y-4 text-sm">
                        <li class="flex gap-3">
                            <x-site.icon name="pin" class="mt-0.5 size-5 shrink-0 text-ivory" />
                            <span>
                                @if ($address)
                                    <span class="whitespace-pre-line">{{ $address }}</span>
                                    @if ($mapUrl)
                                        <a href="{{ $mapUrl }}" rel="noopener" target="_blank" class="mt-1 block underline underline-offset-4 hover:text-ivory">{{ __('Open in Maps') }}<span class="sr-only"> ({{ __('opens in a new tab') }})</span></a>
                                    @endif
                                @else
                                    <span class="italic">{{ __('Address to be confirmed') }}</span>
                                @endif
                            </span>
                        </li>
                        <li class="flex gap-3">
                            <x-site.icon name="phone" class="mt-0.5 size-5 shrink-0 text-ivory" />
                            @if ($phone)
                                <a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" dir="ltr" class="hover:text-ivory">{{ $phone }}</a>
                            @else
                                <span class="italic">{{ __('Phone number coming soon') }}</span>
                            @endif
                        </li>
                        @if ($email)
                            <li class="flex gap-3">
                                <x-site.icon name="mail" class="mt-0.5 size-5 shrink-0 text-ivory" />
                                <a href="mailto:{{ $email }}" class="break-all hover:text-ivory">{{ $email }}</a>
                            </li>
                        @endif
                        <li class="flex gap-3">
                            <x-site.icon name="clock" class="mt-0.5 size-5 shrink-0 text-ivory" />
                            <span class="whitespace-pre-line">{{ $hours ?: __('Opening hours coming soon') }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-ivory/10">
                <div class="site-container flex flex-col gap-4 py-6 text-xs sm:flex-row sm:items-center sm:justify-between">
                    <p>&copy; {{ now()->year }} {{ ClinicDetails::name() }}. {{ __('All rights reserved.') }}</p>
                    <ul class="flex flex-wrap gap-x-6 gap-y-2">
                        <li><a href="{{ route('public.legal.privacy') }}" class="hover:text-ivory">{{ __('Privacy policy') }}</a></li>
                        <li><a href="{{ route('public.legal.terms') }}" class="hover:text-ivory">{{ __('Terms of use') }}</a></li>
                        <li><a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="hover:text-ivory">{{ auth()->check() ? __('My account') : __('Patient & staff login') }}</a></li>
                    </ul>
                </div>
            </div>
        </footer>
    </body>
</html>
