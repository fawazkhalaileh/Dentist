<?php

use App\Actions\Content\SaveBlogCategory;
use App\Livewire\ContentComponent;
use App\Livewire\Forms\BlogCategoryForm;
use App\Models\BlogCategory;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;

new #[Title('Blog categories')] class extends ContentComponent {
    public BlogCategoryForm $form;

    public bool $editing = false;

    /**
     * @return Collection<int, BlogCategory>
     */
    #[Computed]
    public function categories(): Collection
    {
        return BlogCategory::with('translations')->withCount('posts')->orderBy('sort_order')->get();
    }

    public function create(): void
    {
        $this->form->load(null);
        $this->editing = true;
    }

    public function edit(int $id): void
    {
        $this->form->load(BlogCategory::with('translations')->findOrFail($id));
        $this->editing = true;
    }

    public function save(SaveBlogCategory $action): void
    {
        $this->form->prepareSlugs('name');
        $this->form->validate();

        $action->handle($this->form->payload(), $this->form->categoryId ? BlogCategory::findOrFail($this->form->categoryId) : null);

        $this->editing = false;
        unset($this->categories);
        Flux::toast(variant: 'success', text: __('Category saved.'));
    }

    public function delete(int $id): void
    {
        // Articles in the category are kept and become uncategorised.
        BlogCategory::findOrFail($id)->delete();
        unset($this->categories);
        Flux::toast(variant: 'success', text: __('Category deleted.'));
    }
}; ?>

<section class="w-full max-w-3xl">
    <x-ui.page-header :title="__('Blog categories')">
        <x-slot:actions>
            <flux:button :href="route('clinic.content.blog.index')" wire:navigate>{{ __('Back to blog') }}</flux:button>
            <flux:button variant="primary" icon="plus" wire:click="create">{{ __('New category') }}</flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($editing)
        <flux:card class="mb-6">
            <form wire:submit="save" class="space-y-4">
                <x-cms.locale-tabs>
                    @foreach (\App\Support\Locales::supported() as $locale)
                        <div x-show="tab === @js($locale)" x-cloak role="tabpanel" dir="{{ \App\Support\Locales::dir($locale) }}" lang="{{ $locale }}" class="space-y-4">
                            <flux:input wire:model="form.translations.{{ $locale }}.name" :label="__('Name')" />
                            <flux:input wire:model="form.translations.{{ $locale }}.slug" :label="__('URL slug')" :description="__('Leave empty to create it from the name.')" dir="ltr" />
                        </div>
                    @endforeach
                </x-cms.locale-tabs>
                <flux:input type="number" min="0" wire:model="form.sort_order" :label="__('Display order')" class="max-w-40" />
                <div class="flex gap-2">
                    <flux:button type="submit" variant="primary">{{ __('Save category') }}</flux:button>
                    <flux:button wire:click="$set('editing', false)">{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    @if ($this->categories->isEmpty())
        <x-ui.empty-state :title="__('No categories yet')" />
    @else
        <ul class="divide-y divide-zinc-200 rounded-lg border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
            @foreach ($this->categories as $category)
                <li class="flex items-center justify-between gap-4 px-4 py-3" wire:key="category-{{ $category->id }}">
                    <div>
                        <span class="font-medium">{{ $category->translationOrFirst()?->name }}</span>
                        <span class="ms-2 text-xs uppercase text-zinc-500">{{ implode(' · ', $category->translatedLocales()) }}</span>
                        <span class="ms-2 text-xs text-zinc-500">{{ trans_choice(':count article|:count articles', $category->posts_count) }}</span>
                    </div>
                    <div class="flex gap-2">
                        <flux:button size="sm" wire:click="edit({{ $category->id }})">{{ __('Edit') }}</flux:button>
                        <flux:button size="sm" variant="danger" wire:click="delete({{ $category->id }})"
                                     wire:confirm="{{ __('Delete this category? Its articles are kept without a category.') }}">{{ __('Delete') }}</flux:button>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</section>
