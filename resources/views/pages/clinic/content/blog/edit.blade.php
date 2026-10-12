<?php

use App\Actions\Content\SaveBlogPost;
use App\Actions\Media\StoreMedia;
use App\Livewire\ContentComponent;
use App\Livewire\Forms\BlogPostForm;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Media;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Title('Article')] class extends ContentComponent {
    use WithFileUploads;

    public BlogPostForm $form;

    public ?BlogPost $post = null;

    /** @var TemporaryUploadedFile|null */
    public $featuredUpload = null;

    public function mount(?BlogPost $post = null): void
    {
        $this->post = $post?->exists ? $post->load('translations') : null;
        $this->form->load($this->post);
    }

    /**
     * @return Collection<int, BlogCategory>
     */
    #[Computed]
    public function categories(): Collection
    {
        return BlogCategory::with('translations')->orderBy('sort_order')->get();
    }

    #[Computed]
    public function currentImage(): ?Media
    {
        return $this->form->featured_media_id ? Media::find($this->form->featured_media_id) : null;
    }

    public function removeImage(): void
    {
        $this->featuredUpload = null;
        $this->form->featured_media_id = null;
    }

    public function save(SaveBlogPost $savePost, StoreMedia $storeMedia): void
    {
        $this->form->prepareSlugs();
        $this->validate(['featuredUpload' => ['nullable', ...StoreMedia::rules()]]);
        $this->form->validate();

        if ($this->featuredUpload) {
            $this->form->featured_media_id = $storeMedia->handle($this->featuredUpload, $this->actor())->id;
            $this->featuredUpload = null;
        }

        $isNew = $this->post === null;
        $this->post = $savePost->handle($this->form->payload(), $this->actor(), $this->post);

        Flux::toast(variant: 'success', text: __('Article saved.'));

        if ($isNew) {
            $this->redirectRoute('clinic.content.blog.edit', $this->post, navigate: true);
        }
    }
}; ?>

<section class="w-full max-w-4xl">
    <x-ui.page-header :title="$post ? __('Edit article') : __('New article')">
        <x-slot:actions>
            <flux:button :href="route('clinic.content.blog.index')" wire:navigate>{{ __('Back to blog') }}</flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <form wire:submit="save" class="space-y-8">
        <flux:card class="space-y-4">
            <flux:heading size="lg">{{ __('Content') }}</flux:heading>
            <flux:text>{{ __('Write the article in one or both languages. Each language is published only if it has a title and text.') }}</flux:text>

            <x-cms.locale-tabs>
                @foreach (\App\Support\Locales::supported() as $locale)
                    <div x-show="tab === @js($locale)" x-cloak role="tabpanel" dir="{{ \App\Support\Locales::dir($locale) }}" lang="{{ $locale }}" class="space-y-4" wire:key="post-{{ $locale }}">
                        <flux:input wire:model="form.translations.{{ $locale }}.title" :label="__('Title')" />
                        <flux:input wire:model="form.translations.{{ $locale }}.slug" :label="__('URL slug')" :description="__('Leave empty to create it from the title.')" dir="ltr" />
                        <flux:textarea wire:model="form.translations.{{ $locale }}.excerpt" :label="__('Excerpt')" rows="2" :description="__('Short summary shown in article lists.')" />
                        <div>
                            <flux:textarea wire:model="form.translations.{{ $locale }}.body" :label="__('Article text')" rows="14" />
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

        <flux:card class="space-y-4">
            <flux:heading size="lg">{{ __('Settings') }}</flux:heading>

            <x-cms.image-upload :label="__('Featured image')" model="featuredUpload" :upload="$featuredUpload" :current="$this->currentImage" remove="removeImage" />

            <div class="grid gap-4 sm:grid-cols-3">
                <flux:select wire:model="form.status" :label="__('Status')">
                    <flux:select.option value="draft">{{ __('Draft') }}</flux:select.option>
                    <flux:select.option value="published">{{ __('Published') }}</flux:select.option>
                </flux:select>
                <flux:select wire:model="form.blog_category_id" :label="__('Category')">
                    <flux:select.option value="">{{ __('None') }}</flux:select.option>
                    @foreach ($this->categories as $category)
                        <flux:select.option value="{{ $category->id }}">{{ $category->translationOrFirst()?->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input type="datetime-local" wire:model="form.published_at" :label="__('Publish date')" :description="__('Optional. A future date schedules the article.')" />
            </div>
        </flux:card>

        <div class="flex items-center gap-3">
            <flux:button type="submit" variant="primary">{{ __('Save article') }}</flux:button>
            <span wire:loading wire:target="save" class="text-sm text-zinc-500">{{ __('Saving…') }}</span>
        </div>
    </form>
</section>
