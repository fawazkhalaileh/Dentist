<?php

use App\Actions\Patients\ArchivePatient;
use App\Enums\Permission;
use App\Models\Patient;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Patients')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public bool $showArchived = false;

    public function boot(): void
    {
        $this->authorize('viewAny', Patient::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedShowArchived(): void
    {
        $this->resetPage();
    }

    public function canArchive(): bool
    {
        return $this->user()->can(Permission::PatientsArchive->value);
    }

    /**
     * Dentists only see their care team; staff with "view all" see everyone.
     *
     * @return LengthAwarePaginator<int, Patient>
     */
    #[Computed]
    public function patients(): LengthAwarePaginator
    {
        $user = $this->user();
        $search = trim($this->search);

        return Patient::query()
            ->when($this->showArchived && $this->canArchive(), fn (Builder $q) => $q->onlyTrashed())
            ->unless($user->can(Permission::PatientsViewAll->value), fn (Builder $q) => $q->inCareTeamOf($user))
            ->search($search)
            ->orderBy('last_name')->orderBy('first_name')
            ->paginate(25);
    }

    public function restore(int $id, ArchivePatient $archive): void
    {
        $patient = Patient::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $patient);

        $archive->restore($patient);
        unset($this->patients);
        Flux::toast(variant: 'success', text: __('Patient restored.'));
    }

    private function user(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}; ?>

<section class="w-full">
    <x-ui.page-header :title="__('Patients')">
        <x-slot:actions>
            @can('create', \App\Models\Patient::class)
                <flux:button variant="primary" icon="plus" :href="route('clinic.patients.create')" wire:navigate>{{ __('New patient') }}</flux:button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle" class="mb-4">
            <flux:callout.text>{{ session('status') }}</flux:callout.text>
        </flux:callout>
    @endif

    <div class="mb-4 flex flex-wrap items-end gap-4">
        <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" :placeholder="__('Name, patient number, phone or email')" icon="magnifying-glass" class="w-full max-w-md" autofocus />
        @if ($this->canArchive())
            <flux:checkbox wire:model.live="showArchived" :label="__('Show archived patients')" />
        @endif
    </div>

    @if ($this->patients->isEmpty())
        <x-ui.empty-state
            :title="$search !== '' ? __('No patients match your search') : ($showArchived ? __('No archived patients') : __('No patients yet'))"
            :description="$search === '' && ! $showArchived && auth()->user()->can('create', \App\Models\Patient::class) ? __('Register the first patient with “New patient”.') : null" />
    @else
        <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-start text-sm">
                <thead class="bg-zinc-50 text-zinc-600 dark:bg-zinc-900 dark:text-zinc-400">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-start font-medium">{{ __('Patient') }}</th>
                        <th scope="col" class="px-4 py-3 text-start font-medium">{{ __('Number') }}</th>
                        <th scope="col" class="px-4 py-3 text-start font-medium">{{ __('Date of birth') }}</th>
                        <th scope="col" class="px-4 py-3 text-start font-medium">{{ __('Phone') }}</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($this->patients as $patient)
                        <tr wire:key="patient-{{ $patient->id }}">
                            <td class="px-4 py-3 font-medium">
                                @if ($patient->trashed())
                                    {{ $patient->full_name }}
                                @else
                                    <a href="{{ route('clinic.patients.show', $patient) }}" wire:navigate class="hover:underline">{{ $patient->full_name }}</a>
                                @endif
                            </td>
                            <td class="px-4 py-3" dir="ltr">{{ $patient->patient_number }}</td>
                            <td class="px-4 py-3">{{ $patient->date_of_birth?->format('Y-m-d') }}</td>
                            <td class="px-4 py-3" dir="ltr">{{ $patient->phone }}</td>
                            <td class="px-4 py-3 text-end">
                                @if ($patient->trashed())
                                    <flux:button size="sm" wire:click="restore({{ $patient->id }})">{{ __('Restore') }}</flux:button>
                                @else
                                    <flux:button size="sm" :href="route('clinic.patients.show', $patient)" wire:navigate>{{ __('Open') }}</flux:button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $this->patients->links() }}</div>
    @endif
</section>
