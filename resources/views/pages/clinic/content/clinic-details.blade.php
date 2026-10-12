<?php

use App\Livewire\ContentComponent;
use App\Livewire\Forms\ClinicDetailsForm;
use App\Support\ClinicDetails;
use Flux\Flux;
use Livewire\Attributes\Title;

new #[Title('Clinic contact details')] class extends ContentComponent {
    public ClinicDetailsForm $form;

    public function mount(): void
    {
        $this->form->load();
    }

    public function save(): void
    {
        $this->form->validate();

        ClinicDetails::put($this->form->payload(), $this->actor()->id);

        Flux::toast(variant: 'success', text: __('Contact details saved.'));
    }
}; ?>

<section class="w-full max-w-3xl">
    <x-ui.page-header :title="__('Clinic contact details')" :description="__('Shown on the contact page, in the footer and to search engines.')" />

    <form wire:submit="save" class="space-y-6">
        <flux:card class="grid gap-4 sm:grid-cols-2">
            <flux:input wire:model="form.phone" :label="__('Phone')" type="tel" dir="ltr" />
            <flux:input wire:model="form.whatsapp" :label="__('WhatsApp number')" type="tel" dir="ltr" />
            <flux:input wire:model="form.email" :label="__('Email')" type="email" />
            <flux:input wire:model="form.map_url" :label="__('Map link')" type="url" placeholder="https://" dir="ltr" />
        </flux:card>

        <flux:card>
            <x-cms.locale-tabs :error-keys="['form.address.{locale}', 'form.opening_hours.{locale}']">
                @foreach (\App\Support\Locales::supported() as $locale)
                    <div x-show="tab === @js($locale)" x-cloak role="tabpanel" dir="{{ \App\Support\Locales::dir($locale) }}" lang="{{ $locale }}" class="space-y-4">
                        <flux:textarea wire:model="form.address.{{ $locale }}" :label="__('Address')" rows="3" />
                        <flux:textarea wire:model="form.opening_hours.{{ $locale }}" :label="__('Opening hours')" rows="4" :description="__('One line per day or group of days.')" />
                    </div>
                @endforeach
            </x-cms.locale-tabs>
        </flux:card>

        <flux:button type="submit" variant="primary">{{ __('Save details') }}</flux:button>
    </form>
</section>
