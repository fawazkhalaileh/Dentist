@props(['status'])

@use('App\Enums\TreatmentStatus')

@php
    $status = $status instanceof TreatmentStatus ? $status : TreatmentStatus::from($status);
    $color = 'var(--status-'.$status->colorToken().')';
@endphp

{{-- Colour is a workflow cue only; the text label always carries the meaning. --}}
<span {{ $attributes->class('inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium') }}
      style="border-color: {{ $color }}; color: {{ $color }}"
      data-status="{{ $status->value }}">
    <span aria-hidden="true" class="inline-block size-2 rounded-full" style="background-color: {{ $color }}"></span>
    {{ $status->label() }}
</span>
