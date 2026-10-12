<?php

use App\Livewire\ContentComponent;
use App\Models\Service;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;

new #[Title('Website services')] class extends ContentComponent {
    /**
     * @return Collection<int, Service>
     */
    #[Computed]
    public function services(): Collection
    {
        return Service::with('translations')->withCount('appointments')->orderBy('sort_order')->orderBy('id')->get();
    }

    public function delete(int $id): void
    {
        $service = Service::withCount('appointments')->findOrFail($id);

        // Appointments keep a reference to the service; unpublish instead of deleting.
        if ($service->appointments_count > 0) {
            Flux::toast(variant: 'danger', text: __('This service has appointments and cannot be deleted. Set it to draft instead.'));

            return;
        }

        $service->delete();
        unset($this->services);
        Flux::toast(variant: 'success', text: __('Service deleted.'));
    }
}; ?>

<section class="w-full">
    <x-ui.page-header :title="__('Website services')" :description="__('Service pages shown on the public website.')">
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" :href="route('clinic.content.services.create')" wire:navigate>{{ __('New service') }}</flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($this->services->isEmpty())
        <x-ui.empty-state :title="__('No services yet')" :description="__('Create the first service page for the website.')" />
    @else
        <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-start text-sm">
                <thead class="bg-zinc-50 text-zinc-600 dark:bg-zinc-900 dark:text-zinc-400">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-start font-medium">{{ __('Title') }}</th>
                        <th scope="col" class="px-4 py-3 text-start font-medium">{{ __('Languages') }}</th>
                        <th scope="col" class="px-4 py-3 text-start font-medium">{{ __('Status') }}</th>
                        <th scope="col" class="px-4 py-3 text-start font-medium">{{ __('Order') }}</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($this->services as $service)
                        <tr wire:key="service-{{ $service->id }}">
                            <td class="px-4 py-3 font-medium">{{ $service->translationOrFirst()?->title ?? __('(untitled)') }}</td>
                            <td class="px-4 py-3 uppercase">{{ implode(' · ', $service->translatedLocales()) }}</td>
                            <td class="px-4 py-3"><x-cms.publication-badge :status="$service->status" /></td>
                            <td class="px-4 py-3">{{ $service->sort_order }}</td>
                            <td class="px-4 py-3 text-end">
                                <div class="flex justify-end gap-2">
                                    <flux:button size="sm" :href="route('clinic.content.services.edit', $service)" wire:navigate>{{ __('Edit') }}</flux:button>
                                    <flux:button size="sm" variant="danger"
                                                 wire:click="delete({{ $service->id }})"
                                                 wire:confirm="{{ __('Delete this service and all its content? This cannot be undone.') }}">
                                        {{ __('Delete') }}
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
