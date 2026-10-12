@props([
    // Error key patterns per locale; "{locale}" is replaced, e.g. ['form.translations.{locale}.*'].
    'errorKeys' => ['form.translations.{locale}.*'],
])

@use('App\Support\Locales')

@php
    $locales = Locales::supported();
    $withErrors = [];
    foreach ($locales as $l) {
        $patterns = array_map(fn ($p) => str_replace('{locale}', $l, $p), $errorKeys);
        $withErrors[$l] = collect($errors->getBag('default')->keys())->contains(fn ($key) => \Illuminate\Support\Str::is($patterns, $key));
    }
    $initial = collect($withErrors)->filter()->keys()->first() ?? Locales::default();
@endphp

{{-- Language tabs for translated fields. Panels inside use x-show="tab === 'xx'". --}}
<div x-data="{ tab: @js($initial) }" {{ $attributes }}>
    <div role="tablist" aria-label="{{ __('Language') }}" class="mb-4 flex gap-1 border-b border-zinc-200 dark:border-zinc-700">
        @foreach ($locales as $l)
            <button type="button" role="tab"
                    x-on:click="tab = @js($l)"
                    x-bind:aria-selected="(tab === @js($l)).toString()"
                    x-bind:class="tab === @js($l) ? 'border-zinc-800 font-medium dark:border-white' : 'border-transparent text-zinc-500'"
                    class="-mb-px border-b-2 px-4 py-2 text-sm">
                {{ __(config("clinic.locales.{$l}.name")) }}
                @if ($withErrors[$l])
                    <span class="ms-1 text-red-600" aria-hidden="true">●</span>
                    <span class="sr-only">{{ __('(has errors)') }}</span>
                @endif
            </button>
        @endforeach
    </div>

    {{ $slot }}
</div>
