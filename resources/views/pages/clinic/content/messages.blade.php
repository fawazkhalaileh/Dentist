<?php

use App\Livewire\ContentComponent;
use App\Models\ContactMessage;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

new #[Title('Website messages')] class extends ContentComponent {
    use WithPagination;

    #[Url]
    public bool $showHandled = false;

    public function updatedShowHandled(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, ContactMessage>
     */
    #[Computed]
    public function messages(): LengthAwarePaginator
    {
        return ContactMessage::when(! $this->showHandled, fn ($q) => $q->whereNull('handled_at'))
            ->latest()->paginate(20);
    }

    public function markHandled(int $id): void
    {
        ContactMessage::whereKey($id)->update(['handled_at' => now(), 'handled_by' => $this->actor()->id]);
        unset($this->messages);
        Flux::toast(variant: 'success', text: __('Marked as handled.'));
    }
}; ?>

<section class="w-full max-w-4xl">
    <x-ui.page-header :title="__('Website messages')" :description="__('Messages sent through the contact form. They may contain health information: handle them confidentially.')" />

    <flux:checkbox wire:model.live="showHandled" :label="__('Show handled messages')" class="mb-4" />

    @if ($this->messages->isEmpty())
        <x-ui.empty-state :title="__('No messages')" :description="$showHandled ? null : __('All messages have been handled.')" />
    @else
        <ul class="space-y-3">
            @foreach ($this->messages as $message)
                <li class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700" wire:key="message-{{ $message->id }}">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="font-medium">{{ $message->name }}</p>
                            <p class="text-sm text-zinc-500">
                                @if ($message->email) <a href="mailto:{{ $message->email }}" class="underline">{{ $message->email }}</a> @endif
                                @if ($message->phone) <span dir="ltr">{{ $message->phone }}</span> @endif
                                · {{ $message->created_at->format('Y-m-d H:i') }} · <span class="uppercase">{{ $message->locale }}</span>
                            </p>
                        </div>
                        @if ($message->handled_at)
                            <flux:badge size="sm" color="green">{{ __('Handled') }}</flux:badge>
                        @else
                            <flux:button size="sm" wire:click="markHandled({{ $message->id }})">{{ __('Mark as handled') }}</flux:button>
                        @endif
                    </div>
                    <p class="mt-3 whitespace-pre-line text-sm" dir="auto">{{ $message->message }}</p>
                </li>
            @endforeach
        </ul>
        <div class="mt-4">{{ $this->messages->links() }}</div>
    @endif
</section>
