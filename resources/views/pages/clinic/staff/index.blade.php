<?php

use App\Enums\Role;
use App\Livewire\StaffComponent;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;

new #[Title('Dentists and staff')] class extends StaffComponent {
    #[Url]
    public string $search = '';

    #[Url]
    public string $role = '';

    #[Url]
    public bool $showInactive = false;

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function staff(): Collection
    {
        $roles = array_map(fn (Role $r) => $r->value, Role::staff());
        $role = in_array($this->role, $roles, true) ? $this->role : null;
        $search = trim($this->search);

        return User::with(['roles', 'staffProfile'])
            ->whereHas('roles', fn (Builder $q) => $q->whereIn('name', $role ? [$role] : $roles))
            ->when(! $this->showInactive, fn (Builder $q) => $q->where('is_active', true))
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('name', 'like', '%'.addcslashes($search, '%_\\').'%')
                ->orWhere('email', 'like', '%'.addcslashes($search, '%_\\').'%')))
            ->orderBy('name')
            ->get();
    }
}; ?>

<section class="w-full">
    <x-ui.page-header :title="__('Dentists and staff')" :description="__('Staff accounts, their roles, and the dentist profiles shown on the website.')">
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" :href="route('clinic.staff.create')" wire:navigate>{{ __('Add staff member') }}</flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-4 flex flex-wrap items-end gap-4">
        <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" :placeholder="__('Name or email')" icon="magnifying-glass" class="max-w-xs" />
        <flux:select wire:model.live="role" :label="__('Role')" class="max-w-48">
            <flux:select.option value="">{{ __('All roles') }}</flux:select.option>
            @foreach (\App\Enums\Role::staff() as $staffRole)
                <flux:select.option value="{{ $staffRole->value }}">{{ $staffRole->label() }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:checkbox wire:model.live="showInactive" :label="__('Show deactivated accounts')" />
    </div>

    @if ($this->staff->isEmpty())
        <x-ui.empty-state :title="__('No staff found')" :description="$search !== '' || $role !== '' ? __('Try a different search or filter.') : __('Add the clinic\'s dentists and receptionists.')" />
    @else
        <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-start text-sm">
                <thead class="bg-zinc-50 text-zinc-600 dark:bg-zinc-900 dark:text-zinc-400">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-start font-medium">{{ __('Name') }}</th>
                        <th scope="col" class="px-4 py-3 text-start font-medium">{{ __('Role') }}</th>
                        <th scope="col" class="px-4 py-3 text-start font-medium">{{ __('Status') }}</th>
                        <th scope="col" class="px-4 py-3 text-start font-medium">{{ __('Two-factor') }}</th>
                        <th scope="col" class="px-4 py-3 text-start font-medium">{{ __('Last sign-in') }}</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($this->staff as $member)
                        @php $memberRole = \App\Enums\Role::tryFrom((string) $member->roles->first()?->name); @endphp
                        <tr wire:key="staff-{{ $member->id }}">
                            <td class="px-4 py-3">
                                <span class="font-medium">{{ $member->name }}</span>
                                <span class="block text-xs text-zinc-500">{{ $member->email }}</span>
                            </td>
                            <td class="px-4 py-3">
                                {{ $memberRole?->label() }}
                                @if ($memberRole === \App\Enums\Role::Dentist && $member->staffProfile?->is_public)
                                    <span class="block text-xs text-zinc-500">{{ __('Shown on website') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($member->is_active)
                                    <flux:badge size="sm" color="green">{{ __('Active') }}</flux:badge>
                                @else
                                    <flux:badge size="sm" color="zinc">{{ __('Deactivated') }}</flux:badge>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($member->hasConfirmedTwoFactor())
                                    {{ __('Set up') }}
                                @else
                                    <span class="text-amber-700 dark:text-amber-400">{{ __('Not set up') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $member->last_login_at?->format('Y-m-d H:i') ?? __('Never') }}</td>
                            <td class="px-4 py-3 text-end">
                                <flux:button size="sm" :href="route('clinic.staff.edit', $member)" wire:navigate>{{ __('Edit') }}</flux:button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
