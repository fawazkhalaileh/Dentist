@props(['markdown'])

{{-- Staff-written Markdown. App\Support\Markdown strips raw HTML and unsafe links. --}}
<div {{ $attributes->class('prose') }}>{!! \App\Support\Markdown::toHtml($markdown) !!}</div>
