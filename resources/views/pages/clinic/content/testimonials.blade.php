<?php

use App\Actions\Content\SaveTestimonial;
use App\Livewire\ContentComponent;
use App\Livewire\Forms\TestimonialForm;
use App\Models\Service;
use App\Models\Testimonial;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;

new #[Title('Testimonials')] class extends ContentComponent {
    public TestimonialForm $form;

    public bool $editing = false;

    /**
     * @return Collection<int, Testimonial>
     */
    #[Computed]
    public function testimonials(): Collection
    {
        return Testimonial::orderBy('locale')->orderBy('sort_order')->orderBy('id')->get();
    }

    /**
     * @return Collection<int, Service>
     */
    #[Computed]
    public function services(): Collection
    {
        return Service::with('translations')->orderBy('sort_order')->get();
    }

    public function create(): void
    {
        $this->resetValidation();
        $this->form->load(null);
        $this->editing = true;
    }

    public function edit(int $id): void
    {
        $this->resetValidation();
        $this->form->load(Testimonial::findOrFail($id));
        $this->editing = true;
    }

    public function save(SaveTestimonial $action): void
    {
        $this->form->validate();

        $action->handle($this->form->payload(), $this->actor(), $this->form->testimonialId ? Testimonial::findOrFail($this->form->testimonialId) : null);

        $this->editing = false;
        unset($this->testimonials);
        Flux::toast(variant: 'success', text: __('Testimonial saved.'));
    }

    public function delete(int $id): void
    {
        Testimonial::findOrFail($id)->delete();
        unset($this->testimonials);
        Flux::toast(variant: 'success', text: __('Testimonial deleted.'));
    }
}; ?>

<section class="w-full max-w-4xl">
    <x-ui.page-header :title="__('Testimonials')" :description="__('Publish only genuine testimonials, with the patient\'s consent and in their own words.')">
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" wire:click="create">{{ __('New testimonial') }}</flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($editing)
        <flux:card class="mb-6">
            <form wire:submit="save" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:select wire:model="form.locale" :label="__('Language')">
                        @foreach (\App\Support\Locales::supported() as $locale)
                            <flux:select.option value="{{ $locale }}">{{ __(config("clinic.locales.{$locale}.name")) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="form.author_name" :label="__('Name shown')" :description="__('As agreed with the patient, e.g. first name and initial.')" />
                </div>
                <flux:textarea wire:model="form.body" :label="__('Testimonial')" rows="4" />
                <div class="grid gap-4 sm:grid-cols-3">
                    <flux:select wire:model="form.rating" :label="__('Rating')">
                        <flux:select.option value="">{{ __('None') }}</flux:select.option>
                        @foreach (range(5, 1) as $n)
                            <flux:select.option value="{{ $n }}">{{ $n }} / 5</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="form.service_id" :label="__('Related service')">
                        <flux:select.option value="">{{ __('None') }}</flux:select.option>
                        @foreach ($this->services as $service)
                            <flux:select.option value="{{ $service->id }}">{{ $service->translationOrFirst()?->title }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input type="number" min="0" wire:model="form.sort_order" :label="__('Display order')" />
                </div>
                <flux:checkbox wire:model="form.consent_confirmed" :label="__('The patient agreed to publication of this testimonial')" />
                <div class="max-w-xs">
                    <flux:select wire:model="form.status" :label="__('Status')">
                        <flux:select.option value="draft">{{ __('Draft') }}</flux:select.option>
                        <flux:select.option value="published">{{ __('Published') }}</flux:select.option>
                    </flux:select>
                </div>
                <div class="flex gap-2">
                    <flux:button type="submit" variant="primary">{{ __('Save testimonial') }}</flux:button>
                    <flux:button wire:click="$set('editing', false)">{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    @if ($this->testimonials->isEmpty())
        <x-ui.empty-state :title="__('No testimonials yet')" />
    @else
        <ul class="divide-y divide-zinc-200 rounded-lg border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
            @foreach ($this->testimonials as $testimonial)
                <li class="flex flex-wrap items-start justify-between gap-4 px-4 py-3" wire:key="testimonial-{{ $testimonial->id }}">
                    <div class="min-w-0 flex-1" dir="{{ \App\Support\Locales::dir($testimonial->locale) }}">
                        <p class="truncate">{{ $testimonial->body }}</p>
                        <p class="mt-1 text-xs text-zinc-500">— {{ $testimonial->author_name }} · <span class="uppercase">{{ $testimonial->locale }}</span></p>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-cms.publication-badge :status="$testimonial->status" />
                        <flux:button size="sm" wire:click="edit({{ $testimonial->id }})">{{ __('Edit') }}</flux:button>
                        <flux:button size="sm" variant="danger" wire:click="delete({{ $testimonial->id }})"
                                     wire:confirm="{{ __('Delete this testimonial?') }}">{{ __('Delete') }}</flux:button>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</section>
