@props(['status', 'publishedAt' => null])

@php
    $status = $status instanceof \App\Enums\PublicationStatus ? $status : \App\Enums\PublicationStatus::from($status);
    $scheduled = $status === \App\Enums\PublicationStatus::Published && $publishedAt && $publishedAt->isFuture();
@endphp

@if ($scheduled)
    <flux:badge size="sm" color="amber">{{ __('Scheduled') }} · {{ $publishedAt->format('Y-m-d H:i') }}</flux:badge>
@elseif ($status === \App\Enums\PublicationStatus::Published)
    <flux:badge size="sm" color="green">{{ $status->label() }}</flux:badge>
@else
    <flux:badge size="sm" color="zinc">{{ $status->label() }}</flux:badge>
@endif
