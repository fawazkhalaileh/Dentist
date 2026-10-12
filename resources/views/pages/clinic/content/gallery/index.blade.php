<?php

use App\Livewire\ContentComponent;
use App\Models\GalleryItem;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;

new #[Title('Gallery')] class extends ContentComponent {
    /**
     * @return Collection<int, GalleryItem>
     */
    #[Computed]
    public function items(): Collection
    {
        return GalleryItem::with(['translations', 'beforeMedia', 'afterMedia'])->orderBy('sort_order')->orderBy('id')->get();
    }

    public function delete(int $id): void
    {
        // The images stay in the media library; delete them there if no longer needed.
        GalleryItem::findOrFail($id)->delete();
        unset($this->items);
        Flux::toast(variant: 'success', text: __('Gallery item deleted.'));
    }
}; ?>

<section class="w-full">
    <x-ui.page-header :title="__('Before and after gallery')" :description="__('Items are only shown publicly when published and the patient\'s consent is recorded.')">
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" :href="route('clinic.content.gallery.create')" wire:navigate>{{ __('New gallery item') }}</flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->items->isEmpty())
        <x-ui.empty-state :title="__('No gallery items yet')" />
    @else
        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->items as $item)
                <li class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700" wire:key="gallery-{{ $item->id }}">
                    <div class="grid grid-cols-2 gap-2">
                        <img src="{{ $item->beforeMedia->thumbUrl() }}" alt="{{ __('Before') }}" class="aspect-square w-full rounded object-cover">
                        <img src="{{ $item->afterMedia->thumbUrl() }}" alt="{{ __('After') }}" class="aspect-square w-full rounded object-cover">
                    </div>
                    <p class="mt-2 font-medium">{{ $item->translationOrFirst()?->title }}</p>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <x-cms.publication-badge :status="$item->status" />
                        @if ($item->consent_confirmed_at)
                            <flux:badge size="sm" color="blue">{{ __('Consent recorded') }}</flux:badge>
                        @else
                            <flux:badge size="sm" color="red">{{ __('No consent recorded') }}</flux:badge>
                        @endif
                    </div>
                    <div class="mt-3 flex gap-2">
                        <flux:button size="sm" :href="route('clinic.content.gallery.edit', $item)" wire:navigate>{{ __('Edit') }}</flux:button>
                        <flux:button size="sm" variant="danger" wire:click="delete({{ $item->id }})"
                                     wire:confirm="{{ __('Delete this gallery item? The photos remain in the media library.') }}">{{ __('Delete') }}</flux:button>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</section>
