@props([
    'title' => null,
    'description' => null,
    // [locale => url] of this page in each language it exists in. Defaults to the same route in every locale.
    'alternates' => null,
    'image' => null,
    'type' => 'website',
    // schema.org structured data (array), rendered as JSON-LD.
    'schema' => null,
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
        ['label' => 'Services', 'route' => 'public.services.index'],
        ['label' => 'Our dentists', 'route' => 'public.dentists.index'],
        ['label' => 'About', 'route' => 'public.about'],
        ['label' => 'Blog', 'route' => 'public.blog.index'],
        ['label' => 'Contact', 'route' => 'public.contact'],
    ];

    $footer = [
        ['label' => 'Gallery', 'route' => 'public.gallery'],
        ['label' => 'Testimonials', 'route' => 'public.testimonials'],
        ['label' => 'FAQ', 'route' => 'public.faq'],
        ['label' => 'Privacy policy', 'route' => 'public.legal.privacy'],
        ['label' => 'Terms of use', 'route' => 'public.legal.terms'],
    ];

    $pageTitle = filled($title) ? $title.' | '.config('clinic.name') : config('clinic.name');
    $phone = ClinicDetails::get('phone');
    $address = ClinicDetails::get('address');
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

        <meta property="og:site_name" content="{{ config('clinic.name') }}" />
        <meta property="og:title" content="{{ $title ?? config('clinic.name') }}" />
        <meta property="og:type" content="{{ $type }}" />
        <meta property="og:url" content="{{ $canonical }}" />
        <meta property="og:locale" content="{{ $locale }}" />
        @if (filled($description))
            <meta property="og:description" content="{{ $description }}" />
        @endif
        @if (filled($image))
            <meta property="og:image" content="{{ $image }}" />
        @endif

        @if (! empty($schema))
            <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
        @endif

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen flex-col bg-surface text-ink antialiased">
        <x-ui.skip-link />

        <header class="border-b border-line">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4">
                <a href="{{ route('public.home') }}" class="text-lg font-semibold">{{ config('clinic.name') }}</a>

                {{-- Desktop navigation --}}
                <nav aria-label="{{ __('Main navigation') }}" class="hidden md:block">
                    <ul class="flex items-center gap-6 text-sm">
                        @foreach ($nav as $item)
                            <li>
                                <a href="{{ route($item['route']) }}"
                                   @if (request()->routeIs($item['route'], str_replace('.index', '.*', $item['route']))) aria-current="page" class="font-semibold" @else class="hover:underline" @endif
                                >{{ __($item['label']) }}</a>
                            </li>
                        @endforeach
                    </ul>
                </nav>

                <div class="hidden items-center gap-4 text-sm md:flex">
                    <x-ui.language-switcher :alternates="$switcher" />
                    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="hover:underline">
                        {{ auth()->check() ? __('My account') : __('Log in') }}
                    </a>
                    <a href="{{ route('public.booking') }}" class="rounded-md bg-brand px-4 py-2 font-medium text-brand-contrast">
                        {{ __('Request an appointment') }}
                    </a>
                </div>

                {{-- Mobile navigation: native <details>, works without JavaScript --}}
                <details class="relative md:hidden">
                    <summary class="cursor-pointer list-none rounded-md border border-line px-3 py-2 text-sm">{{ __('Menu') }}</summary>
                    <nav aria-label="{{ __('Main navigation') }}" class="absolute end-0 z-10 mt-2 w-64 rounded-md border border-line bg-surface p-4 shadow-lg">
                        <ul class="space-y-3 text-sm">
                            @foreach ($nav as $item)
                                <li><a href="{{ route($item['route']) }}">{{ __($item['label']) }}</a></li>
                            @endforeach
                            <li><a href="{{ auth()->check() ? route('dashboard') : route('login') }}">{{ auth()->check() ? __('My account') : __('Log in') }}</a></li>
                            <li><a href="{{ route('public.booking') }}" class="font-semibold">{{ __('Request an appointment') }}</a></li>
                            <li class="border-t border-line pt-3"><x-ui.language-switcher :alternates="$switcher" /></li>
                        </ul>
                    </nav>
                </details>
            </div>
        </header>

        <main id="main-content" tabindex="-1" class="mx-auto w-full max-w-6xl flex-1 px-4 py-10">
            {{ $slot }}
        </main>

        <footer class="border-t border-line bg-surface-muted">
            <div class="mx-auto grid max-w-6xl gap-6 px-4 py-8 text-sm text-ink-muted md:grid-cols-3">
                <div>
                    <p class="font-medium text-ink">{{ config('clinic.name') }}</p>
                    @if ($address)
                        <p class="mt-1 whitespace-pre-line">{{ $address }}</p>
                    @endif
                    @if ($phone)
                        <p class="mt-1"><a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" class="hover:underline" dir="ltr">{{ $phone }}</a></p>
                    @endif
                </div>
                <nav aria-label="{{ __('Footer navigation') }}" class="md:col-span-2">
                    <ul class="flex flex-wrap gap-4 md:justify-end">
                        @foreach ($footer as $item)
                            <li><a href="{{ route($item['route']) }}" class="hover:underline">{{ __($item['label']) }}</a></li>
                        @endforeach
                    </ul>
                    <p class="mt-4 md:text-end">&copy; {{ now()->year }} {{ config('clinic.name') }}</p>
                </nav>
            </div>
        </footer>
    </body>
</html>
