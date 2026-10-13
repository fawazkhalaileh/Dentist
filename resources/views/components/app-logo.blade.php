@props([
    'sidebar' => false,
])

@php $clinicName = \App\Support\ClinicDetails::name(); @endphp

@if($sidebar)
    <flux:sidebar.brand :name="$clinicName" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-lg bg-brand text-brand-contrast">
            <x-app-logo-icon class="size-5" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand :name="$clinicName" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-lg bg-brand text-brand-contrast">
            <x-app-logo-icon class="size-5" />
        </x-slot>
    </flux:brand>
@endif
