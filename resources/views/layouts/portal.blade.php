@props(['title' => null])

@php
    $user = auth()->user();
    $links = [
        ['label' => 'Overview', 'route' => 'portal.dashboard'],
        ['label' => 'Appointments', 'route' => 'portal.appointments'],
        ['label' => 'Treatments', 'route' => 'portal.treatments'],
        ['label' => 'Documents', 'route' => 'portal.documents'],
        ['label' => 'Account', 'route' => 'profile.edit'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locales::dir() }}">
    <head>
        @include('partials.head', ['title' => $title])
    </head>
    <body class="min-h-screen bg-surface text-ink dark:bg-zinc-900 dark:text-zinc-100">
        <x-ui.skip-link />

        <header class="border-b border-line dark:border-zinc-700">
            <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-4 px-4 py-3">
                <a href="{{ route('portal.dashboard') }}" class="font-semibold">
                    {{ config('clinic.name') }} <span class="font-normal text-ink-muted">· {{ __('Patient portal') }}</span>
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm underline underline-offset-4" data-test="logout-button">
                        {{ __('Log out') }}
                    </button>
                </form>
            </div>

            <nav aria-label="{{ __('Portal navigation') }}" class="mx-auto max-w-5xl px-4">
                <ul class="flex gap-1 overflow-x-auto">
                    @foreach ($links as $link)
                        @php $current = request()->routeIs($link['route']); @endphp
                        <li>
                            <a
                                href="{{ route($link['route']) }}"
                                @if ($current) aria-current="page" @endif
                                @class([
                                    'inline-block border-b-2 px-3 py-2 text-sm whitespace-nowrap',
                                    'border-brand font-medium' => $current,
                                    'border-transparent text-ink-muted hover:text-ink dark:text-zinc-400' => ! $current,
                                ])
                            >{{ __($link['label']) }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </header>

        <main id="main-content" tabindex="-1" class="mx-auto max-w-5xl px-4 py-8">
            {{ $slot }}
        </main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
