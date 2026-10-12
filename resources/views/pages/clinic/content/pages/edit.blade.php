<?php

use App\Actions\Content\SavePage;
use App\Livewire\ContentComponent;
use App\Livewire\Forms\PageForm;
use App\Models\Page;
use Flux\Flux;
use Livewire\Attributes\Title;

new #[Title('Edit page')] class extends ContentComponent {
    public PageForm $form;

    public string $key = '';

    public function mount(string $key): void
    {
        abort_unless(array_key_exists($key, Page::KEYS), 404);

        $this->key = $key;
        $this->form->load(Page::with('translations')->firstOrNew(['key' => $key]));
    }

    public function save(SavePage $action): void
    {
        $this->form->validate();

        $action->handle(Page::firstOrNew(['key' => $this->key]), $this->form->payload());

        Flux::toast(variant: 'success', text: __('Page saved.'));
    }
}; ?>

<section class="w-full max-w-4xl">
    <x-ui.page-header :title="__('Edit page')">
        <x-slot:actions>
            <flux:button :href="route('clinic.content.pages.index')" wire:navigate>{{ __('Back to pages') }}</flux:button>
            <flux:button :href="route(\App\Models\Page::KEYS[$key], ['locale' => app()->getLocale()])" target="_blank" icon="arrow-top-right-on-square">{{ __('View on website') }}</flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <form wire:submit="save" class="space-y-6">
        <flux:card>
            <x-cms.locale-tabs>
                @foreach (\App\Support\Locales::supported() as $locale)
                    <div x-show="tab === @js($locale)" x-cloak role="tabpanel" dir="{{ \App\Support\Locales::dir($locale) }}" lang="{{ $locale }}" class="space-y-4">
                        <flux:input wire:model="form.translations.{{ $locale }}.title" :label="__('Title')" />
                        <div>
                            <flux:textarea wire:model="form.translations.{{ $locale }}.body" :label="__('Text')" rows="16" />
                            <x-cms.markdown-help class="mt-1" />
                        </div>
                        <details class="rounded-md border border-zinc-200 p-3 dark:border-zinc-700">
                            <summary class="cursor-pointer text-sm font-medium">{{ __('Search engine settings') }}</summary>
                            <div class="mt-3 space-y-3">
                                <flux:input wire:model="form.translations.{{ $locale }}.seo_title" :label="__('SEO title')" />
                                <flux:textarea wire:model="form.translations.{{ $locale }}.meta_description" :label="__('Meta description')" rows="2" />
                            </div>
                        </details>
                    </div>
                @endforeach
            </x-cms.locale-tabs>
        </flux:card>

        <div class="max-w-xs">
            <flux:select wire:model="form.status" :label="__('Status')">
                <flux:select.option value="draft">{{ __('Draft') }}</flux:select.option>
                <flux:select.option value="published">{{ __('Published') }}</flux:select.option>
            </flux:select>
        </div>

        <flux:button type="submit" variant="primary">{{ __('Save page') }}</flux:button>
    </form>
</section>
