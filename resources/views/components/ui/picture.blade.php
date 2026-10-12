@props([
    'media',
    // Fallback when the image has no alt text in this language. Use '' for decorative images.
    'alt' => null,
    'sizes' => '(min-width: 768px) 50vw, 100vw',
    'eager' => false,
])

@php
    /** @var \App\Models\Media $media */
    $altText = $media->altFor() ?: ($alt ?? '');
@endphp

<img
    src="{{ $media->url() }}"
    srcset="{{ $media->thumbUrl() }} {{ \App\Models\Media::THUMB_WIDTH }}w, {{ $media->url() }} {{ $media->width }}w"
    sizes="{{ $sizes }}"
    width="{{ $media->width }}"
    height="{{ $media->height }}"
    alt="{{ $altText }}"
    loading="{{ $eager ? 'eager' : 'lazy' }}"
    decoding="async"
    {{ $attributes->class('h-auto max-w-full') }}
/>
