@props(['eyebrow' => null, 'title', 'id' => null, 'align' => 'start', 'light' => false])

<div {{ $attributes->class(['max-w-2xl', 'mx-auto text-center' => $align === 'center']) }}>
    @if ($eyebrow)
        <p @class(['eyebrow', 'justify-center' => $align === 'center', '!text-ivory/80' => $light])>{{ $eyebrow }}</p>
    @endif
    <h2 @if ($id) id="{{ $id }}" @endif @class(['mt-4 text-4xl leading-[1.08] md:text-5xl', 'text-ivory' => $light])>{{ $title }}</h2>
    @if ($slot->isNotEmpty())
        <div @class(['mt-5 text-lg leading-relaxed', 'text-ink-muted' => ! $light, 'text-ivory/75' => $light])>{{ $slot }}</div>
    @endif
</div>
