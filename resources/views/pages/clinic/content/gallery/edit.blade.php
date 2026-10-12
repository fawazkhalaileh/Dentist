<?php

use App\Actions\Content\SaveGalleryItem;
use App\Actions\Media\StoreMedia;
use App\Livewire\ContentComponent;
use App\Livewire\Forms\GalleryItemForm;
use App\Models\GalleryItem;
use App\Models\Media;
use App\Models\Service;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Title('Gallery item')] class extends ContentComponent {
    use WithFileUploads;

    public GalleryItemForm $form;

    public ?GalleryItem $item = null;

    /** @var TemporaryUploadedFile|null */
    public $beforeUpload = null;

    /** @var TemporaryUploadedFile|null */
    public $afterUpload = null;

    public function mount(?GalleryItem $item = null): void
    {
        $this->item = $item?->exists ? $item->load('translations') : null;
        $this->form->load($this->item);
    }

    /**
     * @return Collection<int, Service>
     */
    #[Computed]
    public function services(): Collection
    {
        return Service::with('translations')->orderBy('sort_order')->get();
    }

    #[Computed]
    public function beforeImage(): ?Media
    {
        return $this->form->before_media_id ? Media::find($this->form->before_media_id) : null;
    }

    #[Computed]
    public function afterImage(): ?Media
    {
        return $this->form->after_media_id ? Media::find($this->form->after_media_id) : null;
    }

    public function save(SaveGalleryItem $saveItem, StoreMedia $storeMedia): void
    {
        $this->validate([
            'beforeUpload' => [$this->form->before_media_id ? 'nullable' : 'required', ...StoreMedia::rules()],
            'afterUpload' => [$this->form->after_media_id ? 'nullable' : 'required', ...StoreMedia::rules()],
        ], [
            'beforeUpload.required' => __('Upload a "before" photo.'),
            'afterUpload.required' => __('Upload an "after" photo.'),
        ]);

        if ($this->beforeUpload) {
            $this->form->before_media_id = $storeMedia->handle($this->beforeUpload, $this->actor())->id;
            $this->beforeUpload = null;
        }
        if ($this->afterUpload) {
            $this->form->after_media_id = $storeMedia->handle($this->afterUpload, $this->actor())->id;
            $this->afterUpload = null;
        }

        $this->form->validate();

        $isNew = $this->item === null;
        $this->item = $saveItem->handle($this->form->payload(), $this->actor(), $this->item);

        Flux::toast(variant: 'success', text: __('Gallery item saved.'));

        if ($isNew) {
            $this->redirectRoute('clinic.content.gallery.edit', $this->item, navigate: true);
        }
    }
}; ?>

<section class="w-full max-w-4xl">
    <x-ui.page-header :title="$item ? __('Edit gallery item') : __('New gallery item')">
        <x-slot:actions>
            <flux:button :href="route('clinic.content.gallery.index')" wire:navigate>{{ __('Back to gallery') }}</flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <form wire:submit="save" class="space-y-6">
        <flux:card class="space-y-4">
            <flux:heading size="lg">{{ __('Photos') }}</flux:heading>
            <flux:callout variant="warning" icon="exclamation-triangle">
                <flux:callout.text>{{ __('Only use photos for which the patient gave written consent to publication. Avoid showing faces or anything that identifies the patient.') }}</flux:callout.text>
            </flux:callout>
            <div class="grid gap-6 md:grid-cols-2">
                <x-cms.image-upload :label="__('Before')" model="beforeUpload" :upload="$beforeUpload" :current="$this->beforeImage" />
                <x-cms.image-upload :label="__('After')" model="afterUpload" :upload="$afterUpload" :current="$this->afterImage" />
            </div>
            <flux:error name="form.after_media_id" />
        </flux:card>

        <flux:card>
            <x-cms.locale-tabs>
                @foreach (\App\Support\Locales::supported() as $locale)
                    <div x-show="tab === @js($locale)" x-cloak role="tabpanel" dir="{{ \App\Support\Locales::dir($locale) }}" lang="{{ $locale }}" class="space-y-4">
                        <flux:input wire:model="form.translations.{{ $locale }}.title" :label="__('Title')" :description="__('For example the treatment shown.')" />
                        <flux:textarea wire:model="form.translations.{{ $locale }}.description" :label="__('Description')" rows="3" />
                    </div>
                @endforeach
            </x-cms.locale-tabs>
        </flux:card>

        <flux:card class="space-y-4">
            <flux:checkbox wire:model="form.consent_confirmed" :label="__('The patient\'s written consent to publish these photos is on file')" />
            @if ($item?->consent_confirmed_at)
                <flux:text>{{ __('Consent recorded on :date.', ['date' => $item->consent_confirmed_at->format('Y-m-d')]) }}</flux:text>
            @endif

            <div class="grid gap-4 sm:grid-cols-3">
                <flux:select wire:model="form.status" :label="__('Status')">
                    <flux:select.option value="draft">{{ __('Draft') }}</flux:select.option>
                    <flux:select.option value="published">{{ __('Published') }}</flux:select.option>
                </flux:select>
                <flux:select wire:model="form.service_id" :label="__('Related service')">
                    <flux:select.option value="">{{ __('None') }}</flux:select.option>
                    @foreach ($this->services as $service)
                        <flux:select.option value="{{ $service->id }}">{{ $service->translationOrFirst()?->title }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input type="number" min="0" wire:model="form.sort_order" :label="__('Display order')" />
            </div>
        </flux:card>

        <flux:button type="submit" variant="primary">{{ __('Save gallery item') }}</flux:button>
    </form>
</section>
