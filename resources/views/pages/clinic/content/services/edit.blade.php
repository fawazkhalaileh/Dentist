<?php

use App\Actions\Content\SaveService;
use App\Actions\Media\StoreMedia;
use App\Livewire\ContentComponent;
use App\Livewire\Forms\ServiceForm;
use App\Models\Media;
use App\Models\Service;
use App\Models\User;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Title('Service')] class extends ContentComponent {
    use WithFileUploads;

    public ServiceForm $form;

    public ?Service $service = null;

    /** @var TemporaryUploadedFile|null */
    public $featuredUpload = null;

    public function mount(?Service $service = null): void
    {
        $this->service = $service?->exists ? $service->load(['translations', 'sections', 'faqs', 'dentists']) : null;
        $this->form->load($this->service);
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function dentists(): Collection
    {
        return User::role('dentist')->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function currentImage(): ?Media
    {
        return $this->form->featured_media_id ? Media::find($this->form->featured_media_id) : null;
    }

    public function addSection(string $locale): void
    {
        $this->form->sections[$locale][] = ['heading' => '', 'body' => ''];
    }

    public function removeSection(string $locale, int $index): void
    {
        unset($this->form->sections[$locale][$index]);
        $this->form->sections[$locale] = array_values($this->form->sections[$locale]);
    }

    public function addFaq(string $locale): void
    {
        $this->form->faqs[$locale][] = ['question' => '', 'answer' => ''];
    }

    public function removeFaq(string $locale, int $index): void
    {
        unset($this->form->faqs[$locale][$index]);
        $this->form->faqs[$locale] = array_values($this->form->faqs[$locale]);
    }

    public function removeImage(): void
    {
        $this->featuredUpload = null;
        $this->form->featured_media_id = null;
    }

    public function save(SaveService $saveService, StoreMedia $storeMedia): void
    {
        $this->form->prepareSlugs();
        $this->validate(['featuredUpload' => ['nullable', ...StoreMedia::rules()]]);
        $this->form->validate();

        if ($this->featuredUpload) {
            $this->form->featured_media_id = $storeMedia->handle($this->featuredUpload, $this->actor())->id;
            $this->featuredUpload = null;
        }

        $isNew = $this->service === null;
        $this->service = $saveService->handle($this->form->payload(), $this->service);

        Flux::toast(variant: 'success', text: __('Service saved.'));

        if ($isNew) {
            $this->redirectRoute('clinic.content.services.edit', $this->service, navigate: true);
        }
    }
}; ?>

<section class="w-full max-w-4xl">
    <x-ui.page-header :title="$service ? __('Edit service') : __('New service')">
        <x-slot:actions>
            <flux:button :href="route('clinic.content.services.index')" wire:navigate>{{ __('Back to services') }}</flux:button>
            @if ($service?->isPublished() && $service->translation())
                <flux:button :href="route('public.services.show', ['locale' => app()->getLocale(), 'slug' => $service->translation()->slug])" target="_blank" icon="arrow-top-right-on-square">{{ __('View on website') }}</flux:button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <form wire:submit="save" class="space-y-8">
        <flux:card class="space-y-4">
            <flux:heading size="lg">{{ __('Content') }}</flux:heading>

            <x-cms.locale-tabs :error-keys="['form.translations.{locale}.*', 'form.sections.{locale}.*', 'form.faqs.{locale}.*']">
                @foreach (\App\Support\Locales::supported() as $locale)
                    <div x-show="tab === @js($locale)" x-cloak role="tabpanel" dir="{{ \App\Support\Locales::dir($locale) }}" lang="{{ $locale }}" class="space-y-4" wire:key="service-{{ $locale }}">
                        @if ($locale !== \App\Support\Locales::default())
                            <flux:text>{{ __('Optional. Leave the title empty to hide this service in this language.') }}</flux:text>
                        @endif

                        <flux:input wire:model="form.translations.{{ $locale }}.title" :label="__('Title')" />
                        <flux:input wire:model="form.translations.{{ $locale }}.slug" :label="__('URL slug')" :description="__('Leave empty to create it from the title.')" dir="ltr" />
                        <flux:textarea wire:model="form.translations.{{ $locale }}.summary" :label="__('Summary')" rows="2" :description="__('One or two sentences shown in listings.')" />
                        <div>
                            <flux:textarea wire:model="form.translations.{{ $locale }}.procedure_information" :label="__('Procedure information')" rows="6" />
                            <x-cms.markdown-help class="mt-1" />
                        </div>

                        <fieldset class="space-y-3">
                            <legend class="text-sm font-medium">{{ __('Content sections') }}</legend>
                            @foreach ($form->sections[$locale] ?? [] as $i => $section)
                                <div class="space-y-2 rounded-md border border-zinc-200 p-3 dark:border-zinc-700" wire:key="section-{{ $locale }}-{{ $i }}">
                                    <flux:input wire:model="form.sections.{{ $locale }}.{{ $i }}.heading" :label="__('Heading')" />
                                    <flux:textarea wire:model="form.sections.{{ $locale }}.{{ $i }}.body" :label="__('Text')" rows="4" />
                                    <flux:button size="sm" variant="ghost" wire:click="removeSection('{{ $locale }}', {{ $i }})">{{ __('Remove section') }}</flux:button>
                                </div>
                            @endforeach
                            <flux:button size="sm" icon="plus" wire:click="addSection('{{ $locale }}')">{{ __('Add section') }}</flux:button>
                        </fieldset>

                        <fieldset class="space-y-3">
                            <legend class="text-sm font-medium">{{ __('Frequently asked questions') }}</legend>
                            @foreach ($form->faqs[$locale] ?? [] as $i => $faq)
                                <div class="space-y-2 rounded-md border border-zinc-200 p-3 dark:border-zinc-700" wire:key="faq-{{ $locale }}-{{ $i }}">
                                    <flux:input wire:model="form.faqs.{{ $locale }}.{{ $i }}.question" :label="__('Question')" />
                                    <flux:textarea wire:model="form.faqs.{{ $locale }}.{{ $i }}.answer" :label="__('Answer')" rows="3" />
                                    <flux:button size="sm" variant="ghost" wire:click="removeFaq('{{ $locale }}', {{ $i }})">{{ __('Remove question') }}</flux:button>
                                </div>
                            @endforeach
                            <flux:button size="sm" icon="plus" wire:click="addFaq('{{ $locale }}')">{{ __('Add question') }}</flux:button>
                        </fieldset>

                        <details class="rounded-md border border-zinc-200 p-3 dark:border-zinc-700">
                            <summary class="cursor-pointer text-sm font-medium">{{ __('Search engine settings') }}</summary>
                            <div class="mt-3 space-y-3">
                                <flux:input wire:model="form.translations.{{ $locale }}.seo_title" :label="__('SEO title')" :description="__('Defaults to the title.')" />
                                <flux:textarea wire:model="form.translations.{{ $locale }}.meta_description" :label="__('Meta description')" rows="2" :description="__('About 150 characters. Defaults to the summary.')" />
                            </div>
                        </details>
                    </div>
                @endforeach
            </x-cms.locale-tabs>
        </flux:card>

        <flux:card class="space-y-4">
            <flux:heading size="lg">{{ __('Settings') }}</flux:heading>

            <x-cms.image-upload :label="__('Featured image')" model="featuredUpload" :upload="$featuredUpload" :current="$this->currentImage" remove="removeImage" />

            <div class="grid gap-4 sm:grid-cols-3">
                <flux:select wire:model="form.status" :label="__('Status')">
                    <flux:select.option value="draft">{{ __('Draft') }}</flux:select.option>
                    <flux:select.option value="published">{{ __('Published') }}</flux:select.option>
                </flux:select>
                <flux:input type="number" min="5" max="480" wire:model="form.duration_minutes" :label="__('Duration (minutes)')" />
                <flux:input type="number" min="0" wire:model="form.sort_order" :label="__('Display order')" />
            </div>

            <flux:checkbox wire:model="form.is_bookable" :label="__('Patients can request this service when booking')" />

            <fieldset>
                <legend class="mb-2 text-sm font-medium">{{ __('Dentists who provide this service') }}</legend>
                @if ($this->dentists->isEmpty())
                    <flux:text>{{ __('No active dentists yet.') }}</flux:text>
                @else
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach ($this->dentists as $dentist)
                            <label class="flex items-center gap-2 text-sm" wire:key="dentist-{{ $dentist->id }}">
                                <input type="checkbox" wire:model="form.dentist_ids" value="{{ $dentist->id }}" class="rounded border-zinc-300">
                                {{ $dentist->name }}
                            </label>
                        @endforeach
                    </div>
                @endif
            </fieldset>
        </flux:card>

        <div class="flex items-center gap-3">
            <flux:button type="submit" variant="primary">{{ __('Save service') }}</flux:button>
            <span wire:loading wire:target="save" class="text-sm text-zinc-500">{{ __('Saving…') }}</span>
        </div>
    </form>
</section>
