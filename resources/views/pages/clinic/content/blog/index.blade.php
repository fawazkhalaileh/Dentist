<?php

use App\Enums\PublicationStatus;
use App\Livewire\ContentComponent;
use App\Models\BlogPost;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

new #[Title('Blog')] class extends ContentComponent {
    use WithPagination;

    #[Url]
    public string $status = '';

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, BlogPost>
     */
    #[Computed]
    public function posts(): LengthAwarePaginator
    {
        return BlogPost::with(['translations', 'author', 'category.translations'])
            ->when(in_array($this->status, ['draft', 'published'], true), fn ($q) => $q->where('status', $this->status))
            ->latest('updated_at')
            ->paginate(20);
    }

    public function delete(int $id): void
    {
        BlogPost::findOrFail($id)->delete();
        unset($this->posts);
        Flux::toast(variant: 'success', text: __('Article deleted.'));
    }
}; ?>

<section class="w-full">
    <x-ui.page-header :title="__('Blog')" :description="__('Articles for the public website.')">
        <x-slot:actions>
            <flux:button :href="route('clinic.content.blog.categories')" wire:navigate>{{ __('Categories') }}</flux:button>
            <flux:button variant="primary" icon="plus" :href="route('clinic.content.blog.create')" wire:navigate>{{ __('New article') }}</flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-4 max-w-xs">
        <flux:select wire:model.live="status" :label="__('Status')">
            <flux:select.option value="">{{ __('All') }}</flux:select.option>
            <flux:select.option value="draft">{{ __('Draft') }}</flux:select.option>
            <flux:select.option value="published">{{ __('Published') }}</flux:select.option>
        </flux:select>
    </div>

    @if ($this->posts->isEmpty())
        <x-ui.empty-state :title="__('No articles found')" />
    @else
        <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 text-zinc-600 dark:bg-zinc-900 dark:text-zinc-400">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-start font-medium">{{ __('Title') }}</th>
                        <th scope="col" class="px-4 py-3 text-start font-medium">{{ __('Languages') }}</th>
                        <th scope="col" class="px-4 py-3 text-start font-medium">{{ __('Category') }}</th>
                        <th scope="col" class="px-4 py-3 text-start font-medium">{{ __('Status') }}</th>
                        <th scope="col" class="px-4 py-3 text-start font-medium">{{ __('Author') }}</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($this->posts as $post)
                        <tr wire:key="post-{{ $post->id }}">
                            <td class="px-4 py-3 font-medium">{{ $post->translationOrFirst()?->title }}</td>
                            <td class="px-4 py-3 uppercase">{{ implode(' · ', $post->translatedLocales()) }}</td>
                            <td class="px-4 py-3">{{ $post->category?->translationOrFirst()?->name ?? '—' }}</td>
                            <td class="px-4 py-3"><x-cms.publication-badge :status="$post->status" :published-at="$post->published_at" /></td>
                            <td class="px-4 py-3">{{ $post->author->name }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <flux:button size="sm" :href="route('clinic.content.blog.edit', $post)" wire:navigate>{{ __('Edit') }}</flux:button>
                                    <flux:button size="sm" variant="danger" wire:click="delete({{ $post->id }})"
                                                 wire:confirm="{{ __('Delete this article in all languages? This cannot be undone.') }}">{{ __('Delete') }}</flux:button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $this->posts->links() }}</div>
    @endif
</section>
