<?php

use App\Actions\Content\SaveFaq;
use App\Livewire\ContentComponent;
use App\Livewire\Forms\FaqForm;
use App\Models\Faq;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;

new #[Title('FAQs')] class extends ContentComponent {
    public FaqForm $form;

    public bool $editing = false;

    /**
     * @return Collection<int, Faq>
     */
    #[Computed]
    public function faqs(): Collection
    {
        return Faq::orderBy('locale')->orderBy('sort_order')->orderBy('id')->get();
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
        $this->form->load(Faq::findOrFail($id));
        $this->editing = true;
    }

    public function save(SaveFaq $action): void
    {
        $this->form->validate();

        $action->handle($this->form->payload(), $this->form->faqId ? Faq::findOrFail($this->form->faqId) : null);

        $this->editing = false;
        unset($this->faqs);
        Flux::toast(variant: 'success', text: __('Question saved.'));
    }

    public function delete(int $id): void
    {
        Faq::findOrFail($id)->delete();
        unset($this->faqs);
        Flux::toast(variant: 'success', text: __('Question deleted.'));
    }
}; ?>

<section class="w-full max-w-4xl">
    <x-ui.page-header :title="__('Frequently asked questions')" :description="__('General questions shown on the FAQ page. Service-specific questions are edited on each service.')">
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" wire:click="create">{{ __('New question') }}</flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($editing)
        <flux:card class="mb-6">
            <form wire:submit="save" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-3">
                    <flux:select wire:model="form.locale" :label="__('Language')">
                        @foreach (\App\Support\Locales::supported() as $locale)
                            <flux:select.option value="{{ $locale }}">{{ __(config("clinic.locales.{$locale}.name")) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="form.status" :label="__('Status')">
                        <flux:select.option value="draft">{{ __('Draft') }}</flux:select.option>
                        <flux:select.option value="published">{{ __('Published') }}</flux:select.option>
                    </flux:select>
                    <flux:input type="number" min="0" wire:model="form.sort_order" :label="__('Display order')" />
                </div>
                <flux:input wire:model="form.question" :label="__('Question')" />
                <div>
                    <flux:textarea wire:model="form.answer" :label="__('Answer')" rows="4" />
                    <x-cms.markdown-help class="mt-1" />
                </div>
                <div class="flex gap-2">
                    <flux:button type="submit" variant="primary">{{ __('Save question') }}</flux:button>
                    <flux:button wire:click="$set('editing', false)">{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    @if ($this->faqs->isEmpty())
        <x-ui.empty-state :title="__('No questions yet')" />
    @else
        <ul class="divide-y divide-zinc-200 rounded-lg border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
            @foreach ($this->faqs as $faq)
                <li class="flex flex-wrap items-center justify-between gap-4 px-4 py-3" wire:key="faq-{{ $faq->id }}">
                    <div class="min-w-0 flex-1" dir="{{ \App\Support\Locales::dir($faq->locale) }}">
                        <span class="font-medium">{{ $faq->question }}</span>
                        <span class="ms-2 text-xs uppercase text-zinc-500">{{ $faq->locale }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-cms.publication-badge :status="$faq->status" />
                        <flux:button size="sm" wire:click="edit({{ $faq->id }})">{{ __('Edit') }}</flux:button>
                        <flux:button size="sm" variant="danger" wire:click="delete({{ $faq->id }})"
                                     wire:confirm="{{ __('Delete this question?') }}">{{ __('Delete') }}</flux:button>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</section>
