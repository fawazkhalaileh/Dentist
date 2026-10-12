<?php

use App\Actions\Procedures\AddStarterProcedures;
use App\Enums\Permission;
use App\Livewire\Forms\ProcedureForm;
use App\Models\Procedure;
use App\Support\Audit;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Procedure catalogue')] class extends Component {
    public ProcedureForm $form;

    #[Locked]
    public ?int $editingId = null;

    public bool $editing = false;

    public function boot(): void
    {
        Gate::authorize(Permission::ProceduresManage->value);
    }

    /**
     * @return Collection<int, Procedure>
     */
    #[Computed]
    public function procedures(): Collection
    {
        return Procedure::withCount('treatmentItems')->orderByDesc('is_active')->orderBy('category')->orderBy('name')->get();
    }

    public function create(): void
    {
        $this->resetValidation();
        $this->form->load(null);
        $this->editingId = null;
        $this->editing = true;
    }

    public function edit(int $id): void
    {
        $this->resetValidation();
        $this->form->load(Procedure::findOrFail($id));
        $this->editingId = $id;
        $this->editing = true;
    }

    public function save(): void
    {
        // The locked id decides which procedure is saved, not the browser-editable form.
        $this->form->procedureId = $this->editingId;
        $this->form->prepare();
        $this->form->validate();

        $procedure = $this->editingId ? Procedure::findOrFail($this->editingId) : new Procedure;
        $procedure->fill($this->form->payload())->save();
        Audit::record($this->editingId ? 'procedure.updated' : 'procedure.created', $procedure, ['code' => $procedure->code]);

        $this->editing = false;
        $this->editingId = null;
        unset($this->procedures);
        Flux::toast(variant: 'success', text: __('Procedure saved.'));
    }

    public function delete(int $id): void
    {
        $procedure = Procedure::withCount('treatmentItems')->findOrFail($id);

        // Treatment records point at the procedure: deactivate it instead.
        if ($procedure->treatment_items_count > 0) {
            Flux::toast(variant: 'danger', text: __('This procedure is used in treatment records. Deactivate it instead.'));

            return;
        }

        $procedure->delete();
        Audit::record('procedure.deleted', null, ['code' => $procedure->code]);
        unset($this->procedures);
        Flux::toast(variant: 'success', text: __('Procedure deleted.'));
    }

    public function addStarter(AddStarterProcedures $addStarter): void
    {
        $added = $addStarter->handle();
        unset($this->procedures);

        if ($added > 0) {
            Audit::record('procedure.starter_added', null, ['count' => $added]);
        }

        Flux::toast(variant: 'success', text: $added > 0
            ? trans_choice(':count starter procedure added.|:count starter procedures added.', $added)
            : __('All starter procedures are already in the catalogue.'));
    }
}; ?>

<section class="w-full">
    <x-ui.page-header :title="__('Procedure catalogue')" :description="__('Procedures used in treatment plans and the dental chart.')">
        <x-slot:actions>
            <flux:button wire:click="addStarter" icon="sparkles">{{ __('Add starter procedures') }}</flux:button>
            <flux:button variant="primary" icon="plus" wire:click="create">{{ __('New procedure') }}</flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($editing)
        <flux:card class="mb-6">
            <form wire:submit="save" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-3">
                    <flux:input wire:model="form.code" :label="__('Code')" :description="__('Short internal code, e.g. FILL.')" dir="ltr" class="uppercase" />
                    <flux:input wire:model="form.name" :label="__('Name (English)')" />
                    <flux:input wire:model="form.name_ar" :label="__('Name (Arabic)')" dir="rtl" lang="ar" />
                    <flux:select wire:model="form.category" :label="__('Category')">
                        <flux:select.option value="">{{ __('None') }}</flux:select.option>
                        @foreach (\App\Models\Procedure::CATEGORIES as $category)
                            <flux:select.option value="{{ $category }}">{{ \App\Models\Procedure::categoryLabel($category) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="form.scope" :label="__('Applies to')">
                        @foreach (\App\Models\Procedure::SCOPES as $scope)
                            <flux:select.option value="{{ $scope }}">{{ \App\Models\Procedure::scopeLabel($scope) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input type="number" min="5" max="480" step="5" wire:model="form.default_duration_minutes" :label="__('Usual duration (minutes)')" />
                </div>
                <flux:textarea wire:model="form.description" :label="__('Description')" rows="2" />
                <flux:checkbox wire:model="form.is_active" :label="__('Active (can be chosen for new treatments)')" />
                <div class="flex gap-2">
                    <flux:button type="submit" variant="primary">{{ __('Save procedure') }}</flux:button>
                    <flux:button wire:click="$set('editing', false)">{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    @if ($this->procedures->isEmpty())
        <x-ui.empty-state :title="__('The catalogue is empty')" :description="__('Add the starter procedures, then adjust names and durations to the clinic\'s own.')" />
    @else
        <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-start text-sm">
                <thead class="bg-zinc-50 text-zinc-600 dark:bg-zinc-900 dark:text-zinc-400">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-start font-medium">{{ __('Code') }}</th>
                        <th scope="col" class="px-4 py-3 text-start font-medium">{{ __('Name') }}</th>
                        <th scope="col" class="px-4 py-3 text-start font-medium">{{ __('Category') }}</th>
                        <th scope="col" class="px-4 py-3 text-start font-medium">{{ __('Applies to') }}</th>
                        <th scope="col" class="px-4 py-3 text-start font-medium">{{ __('Duration') }}</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($this->procedures as $procedure)
                        <tr wire:key="procedure-{{ $procedure->id }}" @class(['text-zinc-400' => ! $procedure->is_active])>
                            <td class="px-4 py-3 font-mono" dir="ltr">{{ $procedure->code }}</td>
                            <td class="px-4 py-3">
                                {{ $procedure->name }}
                                @if ($procedure->name_ar) <span class="block text-xs text-zinc-500" dir="rtl" lang="ar">{{ $procedure->name_ar }}</span> @endif
                                @unless ($procedure->is_active) <flux:badge size="sm" color="zinc">{{ __('Inactive') }}</flux:badge> @endunless
                            </td>
                            <td class="px-4 py-3">{{ \App\Models\Procedure::categoryLabel($procedure->category) }}</td>
                            <td class="px-4 py-3">{{ \App\Models\Procedure::scopeLabel($procedure->scope) }}</td>
                            <td class="px-4 py-3">{{ trans_choice(':count minute|:count minutes', $procedure->default_duration_minutes) }}</td>
                            <td class="px-4 py-3 text-end">
                                <div class="flex justify-end gap-2">
                                    <flux:button size="sm" wire:click="edit({{ $procedure->id }})">{{ __('Edit') }}</flux:button>
                                    @if ($procedure->treatment_items_count === 0)
                                        <flux:button size="sm" variant="danger" wire:click="delete({{ $procedure->id }})" wire:confirm="{{ __('Delete this procedure?') }}">{{ __('Delete') }}</flux:button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
