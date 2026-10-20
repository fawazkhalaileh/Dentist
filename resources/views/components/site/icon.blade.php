@props(['name'])

{{-- Line icons for the public website (24px grid, 1.5 stroke). Decorative unless given a label by the caller. --}}
@php
    $paths = [
        'tooth' => '<path d="M7.5 3.5c-2.4 0-4 1.9-4 4.6 0 2.2.9 3.6 1.5 5.4.7 2 .8 4.4 1.6 6 .5 1.1 1.9 1.2 2.4 0 .6-1.6.9-4.5 3-4.5s2.4 2.9 3 4.5c.5 1.2 1.9 1.1 2.4 0 .8-1.6.9-4 1.6-6 .6-1.8 1.5-3.2 1.5-5.4 0-2.7-1.6-4.6-4-4.6-1.9 0-2.7 1-4.5 1s-2.6-1-4.5-1Z"/>',
        'sparkle' => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5 18 18M18 6l-2.5 2.5M8.5 15.5 6 18"/>',
        'whitening' => '<path d="M8 4c-2 0-3.5 1.6-3.5 4 0 1.9.8 3.1 1.3 4.6.6 1.7.7 3.8 1.4 5.2.4 1 1.6 1 2 0 .5-1.4.8-3.8 2.8-3.8"/><path d="M17.5 3.5v3M16 5h3M19 10.5v2M18 11.5h2"/>',
        'veneers' => '<rect x="4" y="5" width="4.5" height="12" rx="2"/><rect x="9.75" y="5" width="4.5" height="13.5" rx="2"/><rect x="15.5" y="5" width="4.5" height="12" rx="2"/>',
        'implant' => '<path d="M8 3.5h8c0 3-1.3 4.5-4 4.5S8 6.5 8 3.5Z"/><path d="M9.5 10h5M10 13h4M10.5 16h3M11 19h2M12 8v13"/>',
        'crown' => '<path d="M4 9l3.5 3L12 6l4.5 6L20 9l-1.5 9h-13L4 9Z"/><path d="M5.5 18h13"/>',
        'aligners' => '<path d="M3.5 9c2.5 4.5 14.5 4.5 17 0"/><path d="M5 9.5v2.5c2.5 3.5 11.5 3.5 14 0V9.5"/><path d="M8 11.5v2M12 12.3v2M16 11.5v2"/>',
        'shield' => '<path d="M12 3.5 5 6v5.5c0 4.2 2.9 7.7 7 9 4.1-1.3 7-4.8 7-9V6l-7-2.5Z"/><path d="m9 12 2 2 4-4"/>',
        'heart' => '<path d="M12 20s-7.5-4.4-7.5-10A4.3 4.3 0 0 1 12 7.4 4.3 4.3 0 0 1 19.5 10c0 5.6-7.5 10-7.5 10Z"/>',
        'plan' => '<rect x="5" y="3.5" width="14" height="17" rx="2"/><path d="M9 8h6M9 12h6M9 16h3"/>',
        'leaf' => '<path d="M5 19c0-8 5-13 14-14 0 9-5 14-13 14"/><path d="M5 19 13 11"/>',
        'chat' => '<path d="M4.5 6.5A2 2 0 0 1 6.5 4.5h11a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H10l-4 3.5v-3.5H6.5a2 2 0 0 1-2-2v-8Z"/>',
        'clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
        'phone' => '<path d="M6.5 3.5h3l1.5 4-2 1.3a10 10 0 0 0 6.2 6.2l1.3-2 4 1.5v3a2 2 0 0 1-2 2A15.5 15.5 0 0 1 4.5 5.5a2 2 0 0 1 2-2Z"/>',
        'pin' => '<path d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0 1 13 0c0 5.4-6.5 11-6.5 11Z"/><circle cx="12" cy="10" r="2.3"/>',
        'mail' => '<rect x="3.5" y="5.5" width="17" height="13" rx="2"/><path d="m4 7 8 6 8-6"/>',
        'calendar' => '<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M4 9.5h16M8.5 3v4M15.5 3v4"/>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h10"/>',
        'close' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'play' => '<path d="M8 5.5v13l10-6.5-10-6.5Z"/>',
        'quote' => '<path d="M9.5 7C6.5 7.8 5 10.2 5 13.5V17h4.5v-4.5H7c0-2 1-3.3 2.5-3.8V7ZM18.5 7c-3 .8-4.5 3.2-4.5 6.5V17h4.5v-4.5H16c0-2 1-3.3 2.5-3.8V7Z"/>',
        'expand' => '<path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/>',
        'search' => '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/>',
        'instagram' => '<rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r=".6" fill="currentColor"/>',
        'facebook' => '<path d="M14 8.5h2.5V5H14a3.5 3.5 0 0 0-3.5 3.5V11H8v3.5h2.5V21H14v-6.5h2.5L17 11h-3V9a.5.5 0 0 1 .5-.5Z"/>',
        'whatsapp' => '<path d="M4.5 19.5 5.6 16A8 8 0 1 1 8.4 18.6l-3.9.9Z"/><path d="M9.3 8.6c.2-.4.6-.5.9-.4l.6.1.8 1.8-.6.8a5 5 0 0 0 2.2 2.2l.8-.6 1.8.8.1.6c0 .3 0 .7-.4.9-1.3 1-4.2-.3-5.4-2.2-.8-1.3-1.3-2.9-.8-4Z"/>',
    ];
@endphp

<svg {{ $attributes->merge(['class' => 'size-6', 'aria-hidden' => 'true']) }} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" focusable="false">{!! $paths[$name] ?? '' !!}</svg>
