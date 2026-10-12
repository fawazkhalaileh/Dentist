<?php

use App\Livewire\ContentComponent;
use App\Models\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;

new #[Title('Website pages')] class extends ContentComponent {
    /**
     * Every expected page, whether or not it has been written yet.
     *
     * @return Collection<string, Page|null>
     */
    #[Computed]
    public function pages(): Collection
    {
        $existing = Page::with('translations')->get()->keyBy('key');

        return collect(Page::KEYS)->map(fn ($route, $key) => $existing->get($key));
    }

    public function label(string $key): string
    {
        return match ($key) {
            'about' => __('About the clinic'),
            'privacy' => __('Privacy policy'),
            'terms' => __('Terms of use'),
            default => $key,
        };
    }
}; ?>

<section class="w-full max-w-3xl">
    <x-ui.page-header :title="__('Website pages')" :description="__('Fixed pages of the public website.')" />

    <ul class="divide-y divide-zinc-200 rounded-lg border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
        @foreach ($this->pages as $key => $page)
            <li class="flex items-center justify-between gap-4 px-4 py-3" wire:key="page-{{ $key }}">
                <div class="flex items-center gap-3">
                    <span class="font-medium">{{ $this->label($key) }}</span>
                    @if ($page)
                        <x-cms.publication-badge :status="$page->status" />
                        <span class="text-xs uppercase text-zinc-500">{{ implode(' · ', $page->translatedLocales()) }}</span>
                    @else
                        <flux:badge size="sm" color="zinc">{{ __('Not written') }}</flux:badge>
                    @endif
                </div>
                <flux:button size="sm" :href="route('clinic.content.pages.edit', $key)" wire:navigate>{{ __('Edit') }}</flux:button>
            </li>
        @endforeach
    </ul>
</section>
