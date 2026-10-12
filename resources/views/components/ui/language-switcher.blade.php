@props(['alternates' => []])

@use('App\Support\Locales')

{{-- Links to the same page in the other supported locales. --}}
<ul {{ $attributes->class('flex items-center gap-3') }} aria-label="{{ __('Language') }}">
    @foreach ($alternates as $locale => $url)
        @continue($locale === app()->getLocale())
        <li>
            <a href="{{ $url }}" hreflang="{{ $locale }}" lang="{{ $locale }}" class="hover:underline">
                {{ Locales::nativeName($locale) }}
            </a>
        </li>
    @endforeach
</ul>
