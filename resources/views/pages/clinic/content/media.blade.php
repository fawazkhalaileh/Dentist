<?php

use App\Actions\Media\DeleteMedia;
use App\Actions\Media\StoreMedia;
use App\Livewire\ContentComponent;
use App\Models\Media;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Title('Media library')] class extends ContentComponent {
    use WithFileUploads, WithPagination;

    /** @var TemporaryUploadedFile|null */
    public $upload = null;

    public ?int $editingId = null;

    /** @var array<string, string> */
    public array $alt = [];

    /**
     * @return LengthAwarePaginator<int, Media>
     */
    #[Computed]
    public function media(): LengthAwarePaginator
    {
        return Media::latest()->paginate(24);
    }

    public function updatedUpload(): void
    {
        $this->validate(['upload' => ['required', ...StoreMedia::rules()]]);

        $media = app(StoreMedia::class)->handle($this->upload, $this->actor());
        $this->upload = null;
        unset($this->media);

        Flux::toast(variant: 'success', text: __('Image uploaded. Add alternative text so everyone can understand it.'));
        $this->editAlt($media->id);
    }

    public function editAlt(int $id): void
    {
        $media = Media::findOrFail($id);
        $this->editingId = $media->id;

        foreach (\App\Support\Locales::supported() as $locale) {
            $this->alt[$locale] = $media->alt[$locale] ?? '';
        }
    }

    public function saveAlt(): void
    {
        $this->validate(['alt.*' => ['nullable', 'string', 'max:255']]);

        Media::findOrFail($this->editingId)->update([
            'alt' => array_filter(array_map('trim', $this->alt)) ?: null,
        ]);

        $this->editingId = null;
        unset($this->media);
        Flux::toast(variant: 'success', text: __('Alternative text saved.'));
    }

    public function delete(int $id, DeleteMedia $deleteMedia): void
    {
        try {
            $deleteMedia->handle(Media::findOrFail($id));
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        unset($this->media);
        Flux::toast(variant: 'success', text: __('Image deleted.'));
    }
}; ?>

<section class="w-full">
    <x-ui.page-header :title="__('Media library')" :description="__('Public website images. Never upload clinical photos or documents here.')" />

    <flux:card class="mb-6">
        <x-cms.image-upload :label="__('Upload an image')" model="upload" :upload="null" />
    </flux:card>

    @if ($editingId)
        <flux:card class="mb-6">
            <form wire:submit="saveAlt" class="space-y-4">
                <flux:heading>{{ __('Alternative text') }}</flux:heading>
                <flux:text>{{ __('Describe what the image shows for people who cannot see it. Leave empty for purely decorative images.') }}</flux:text>
                @foreach (\App\Support\Locales::supported() as $locale)
                    <flux:input wire:model="alt.{{ $locale }}" :label="__(config('clinic.locales.'.$locale.'.name'))" dir="{{ \App\Support\Locales::dir($locale) }}" />
                @endforeach
                <div class="flex gap-2">
                    <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
                    <flux:button wire:click="$set('editingId', null)">{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    @if ($this->media->isEmpty())
        <x-ui.empty-state :title="__('No images yet')" />
    @else
        <ul class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
            @foreach ($this->media as $item)
                <li class="rounded-lg border border-zinc-200 p-2 dark:border-zinc-700" wire:key="media-{{ $item->id }}">
                    <img src="{{ $item->thumbUrl() }}" alt="{{ $item->altFor() }}" class="aspect-square w-full rounded object-cover" loading="lazy">
                    <p class="mt-1 truncate text-xs" title="{{ $item->original_name }}">{{ $item->original_name }}</p>
                    <p class="text-xs text-zinc-500">{{ $item->width }}×{{ $item->height }} · {{ number_format($item->size_bytes / 1024) }} KB</p>
                    @if (! $item->altFor())
                        <p class="text-xs text-amber-700">{{ __('No alt text') }}</p>
                    @endif
                    <div class="mt-2 flex gap-1">
                        <flux:button size="xs" wire:click="editAlt({{ $item->id }})">{{ __('Alt text') }}</flux:button>
                        <flux:button size="xs" variant="danger" wire:click="delete({{ $item->id }})"
                                     wire:confirm="{{ __('Delete this image permanently?') }}">{{ __('Delete') }}</flux:button>
                    </div>
                </li>
            @endforeach
        </ul>
        <div class="mt-4">{{ $this->media->links() }}</div>
    @endif
</section>
