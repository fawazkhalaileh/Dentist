@props([
    // Key in config('site.photos').
    'name',
    'sizes' => '(min-width: 1024px) 50vw, 100vw',
    'eager' => false,
    // Pass '' when the photo is decorative next to text that already says the same.
    'alt' => null,
])

@php
    $photo = config("site.photos.{$name}");
    $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';
    $srcset = collect($photo['widths'])->map(fn ($w) => asset("images/site/{$name}-{$w}.webp")." {$w}w")->implode(', ');
    $largest = max($photo['widths']);
@endphp

<img
    src="{{ asset("images/site/{$name}-{$largest}.webp") }}"
    srcset="{{ $srcset }}"
    sizes="{{ $sizes }}"
    width="{{ $photo['width'] }}"
    height="{{ $photo['height'] }}"
    alt="{{ $alt ?? $photo['alt'][$locale] }}"
    loading="{{ $eager ? 'eager' : 'lazy' }}"
    @if ($eager) fetchpriority="high" @endif
    decoding="async"
    {{ $attributes->class('max-w-full') }}
/>
