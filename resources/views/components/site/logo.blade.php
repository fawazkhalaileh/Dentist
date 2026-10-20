@props([
    // brand (burgundy, for light backgrounds) or light (for dark backgrounds).
    'variant' => 'brand',
    // lockup: mark beside the wordmark (header); stacked: the logo as designed (footer).
    'layout' => 'lockup',
])

{{-- The official Amara Dental logo, used as supplied (background removed only); never redrawn. --}}
@if ($layout === 'stacked')
    <img src="{{ asset("images/brand/logo-{$variant}.webp") }}" width="350" height="360" alt="{{ \App\Support\ClinicDetails::name() }}" {{ $attributes->class('h-auto') }} />
@else
    <span {{ $attributes->class('inline-flex items-center gap-2.5') }}>
        <img src="{{ asset("images/brand/mark-{$variant}.webp") }}" width="123" height="128" alt="" class="h-full w-auto" />
        <img src="{{ asset("images/brand/wordmark-{$variant}.webp") }}" width="341" height="112" alt="{{ \App\Support\ClinicDetails::name() }}" class="h-[68%] w-auto" />
    </span>
@endif
