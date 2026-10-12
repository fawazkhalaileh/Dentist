<?php

use App\Actions\Treatments\AddTreatmentItem;
use App\Actions\Treatments\ChangeTreatmentStatus;
use App\Actions\Treatments\CreateTreatmentPlan;
use App\Enums\Role;
use App\Enums\TreatmentStatus;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\TreatmentItem;
use App\Models\TreatmentPlan;
use App\Models\User;
use App\Support\Audit;
use App\Support\Fdi;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Treatment plans')] class extends Component {
    public Patient $patient;

    /** Tooth filter, e.g. from the dental chart. */
    #[Url]
    public ?int $tooth = null;

    public bool $addingItem = false;

    public bool $addingPlan = false;

    /** @var array{procedure_id: ?int, tooth_number: ?int, surfaces: list<string>, dentist_id: ?int, treatment_plan_id: ?int, planned_for: string, notes: string} */
    public array $item = ['procedure_id' => null, 'tooth_number' => null, 'surfaces' => [], 'dentist_id' => null, 'treatment_plan_id' => null, 'planned_for' => '', 'notes' => ''];

    /** @var array{title: string, dentist_id: ?int} */
    public array $plan = ['title' => '', 'dentist_id' => null];

    /** Item whose cancellation reason is being entered. */
    #[Locked]
    public ?int $cancellingId = null;

    public string $cancelReason = '';

    /** Item whose history is open. */
    #[Locked]
    public ?int $historyId = null;

    public function mount(Patient $patient): void
    {
        $this->authorize('viewClinical', $patient);
        $this->patient = $patient;
        if ($this->tooth !== null && ! Fdi::isValid($this->tooth)) {
            $this->tooth = null;
        }

        Audit::record('clinical.treatments_viewed', $patient);
    }

    /**
     * @return Collection<int, TreatmentPlan>
     */
    #[Computed]
    public function plans(): Collection
    {
        return $this->patient->treatmentPlans()->latest('id')->get();
    }

    /**
     * @return Collection<int, TreatmentItem>
     */
    #[Computed]
    public function items(): Collection
    {
        return $this->patient->treatmentItems()
            ->with(['procedure', 'dentist:id,name', 'surfaces'])
            ->when($this->tooth !== null, fn ($q) => $q->where('tooth_number', $this->tooth))
            ->orderByRaw("FIELD(status, 'in_progress', 'planned', 'completed', 'cancelled')")
            ->orderBy('planned_for')->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, Procedure>
     */
    #[Computed]
    public function procedures(): Collection
    {
        return Procedure::where('is_active', true)->orderBy('category')->orderBy('name')->get();
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function dentists(): Collection
    {
        return User::havingRole(Role::Dentist)->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function selectedProcedure(): ?Procedure
    {
        return $this->item['procedure_id'] ? $this->procedures->firstWhere('id', (int) $this->item['procedure_id']) : null;
    }

    /**
     * @return list<\App\Enums\ToothSurface>
     */
    #[Computed]
    public function availableSurfaces(): array
    {
        return $this->item['tooth_number'] ? Fdi::surfaces((int) $this->item['tooth_number']) : [];
    }

    public function updatedItemToothNumber(): void
    {
        // Surfaces depend on the tooth (incisal vs occlusal).
        $this->item['surfaces'] = [];
    }

    public function openItemForm(): void
    {
        $this->authorize('create', [TreatmentItem::class, $this->patient]);
        $this->resetValidation();
        $user = $this->actor();
        $this->item = [
            'procedure_id' => null,
            'tooth_number' => $this->tooth,
            'surfaces' => [],
            'dentist_id' => $user->hasRoleEnum(Role::Dentist) ? $user->id : $this->patient->dentists()->value('users.id'),
            'treatment_plan_id' => null,
            'planned_for' => '',
            'notes' => '',
        ];
        $this->addingItem = true;
    }

    public function addItem(AddTreatmentItem $add): void
    {
        $this->authorize('create', [TreatmentItem::class, $this->patient]);

        $scope = $this->selectedProcedure?->scope;
        $surfaces = array_map(fn ($s) => $s->value, $this->availableSurfaces);

        $this->validate([
            'item.procedure_id' => ['required', 'integer', Rule::in($this->procedures->pluck('id')->all())],
            'item.tooth_number' => [$scope === 'mouth' || $scope === null ? 'nullable' : 'required', 'nullable', 'integer', Rule::in([...Fdi::permanent(), ...Fdi::primary()])],
            'item.surfaces' => [$scope === 'surface' ? 'required' : 'nullable', 'array'],
            'item.surfaces.*' => [Rule::in($surfaces)],
            'item.dentist_id' => ['required', 'integer', Rule::in($this->dentists->pluck('id')->all())],
            'item.treatment_plan_id' => ['nullable', 'integer', Rule::in($this->plans->filter(fn ($p) => ! in_array($p->status->value, ['completed', 'cancelled'], true))->pluck('id')->all())],
            'item.planned_for' => ['nullable', 'date'],
            'item.notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'item.tooth_number.required' => __('This procedure needs a tooth.'),
            'item.surfaces.required' => __('Choose at least one surface.'),
        ]);

        $add->handle($this->patient, [
            'procedure_id' => (int) $this->item['procedure_id'],
            'tooth_number' => $scope === 'mouth' || ! $this->item['tooth_number'] ? null : (int) $this->item['tooth_number'],
            'surfaces' => $scope === 'surface' ? array_values($this->item['surfaces']) : [],
            'dentist_id' => (int) $this->item['dentist_id'],
            'treatment_plan_id' => $this->item['treatment_plan_id'] ? (int) $this->item['treatment_plan_id'] : null,
            'planned_for' => $this->item['planned_for'] ?: null,
            'notes' => trim($this->item['notes']) ?: null,
        ], $this->actor());

        $this->addingItem = false;
        unset($this->items, $this->plans);
        Flux::toast(variant: 'success', text: __('Treatment added.'));
    }

    public function openPlanForm(): void
    {
        $this->authorize('create', [TreatmentItem::class, $this->patient]);
        $this->resetValidation();
        $user = $this->actor();
        $this->plan = ['title' => '', 'dentist_id' => $user->hasRoleEnum(Role::Dentist) ? $user->id : null];
        $this->addingPlan = true;
    }

    public function addPlan(CreateTreatmentPlan $create): void
    {
        $this->authorize('create', [TreatmentItem::class, $this->patient]);
        $this->validate([
            'plan.title' => ['required', 'string', 'max:255'],
            'plan.dentist_id' => ['required', 'integer', Rule::in($this->dentists->pluck('id')->all())],
        ]);

        $create->handle($this->patient, $this->plan['title'], (int) $this->plan['dentist_id'], null, $this->actor());

        $this->addingPlan = false;
        unset($this->plans);
        Flux::toast(variant: 'success', text: __('Treatment plan created.'));
    }

    public function changeStatus(int $id, string $status, ChangeTreatmentStatus $change): void
    {
        $item = $this->ownItem($id);
        $this->authorize('changeStatus', $item);
        $to = TreatmentStatus::from($status);
        abort_if($to === TreatmentStatus::Cancelled, 422); // cancelling needs a reason: confirmCancel()

        $change->handle($item, $to, $this->actor());
        unset($this->items, $this->plans);
        Flux::toast(variant: 'success', text: __('Treatment marked :status.', ['status' => strtolower($to->label())]));
    }

    public function startCancel(int $id): void
    {
        $this->authorize('changeStatus', $this->ownItem($id));
        $this->cancellingId = $id;
        $this->cancelReason = '';
        $this->resetValidation();
    }

    public function confirmCancel(ChangeTreatmentStatus $change): void
    {
        abort_if($this->cancellingId === null, 422);
        $item = $this->ownItem($this->cancellingId);
        $this->authorize('changeStatus', $item);
        $this->validate(['cancelReason' => ['required', 'string', 'max:1000']], [], ['cancelReason' => __('reason')]);

        $change->handle($item, TreatmentStatus::Cancelled, $this->actor(), $this->cancelReason);

        $this->cancellingId = null;
        unset($this->items, $this->plans);
        Flux::toast(variant: 'success', text: __('Treatment cancelled. It stays in the record.'));
    }

    public function toggleHistory(int $id): void
    {
        $this->ownItem($id);
        $this->historyId = $this->historyId === $id ? null : $id;
    }

    private function ownItem(int $id): TreatmentItem
    {
        return $this->patient->treatmentItems()->findOrFail($id);
    }

    private function actor(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}; ?>

<section class="w-full max-w-6xl">
    <x-ui.page-header :title="__('Treatment plans')" :description="$patient->full_name.' · '.$patient->patient_number">
        <x-slot:actions>
            <flux:button :href="route('clinic.patients.chart', $patient)" wire:navigate icon="squares-2x2">{{ __('Dental chart') }}</flux:button>
            <flux:button :href="route('clinic.patients.show', $patient)" wire:navigate>{{ __('Back to patient') }}</flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    @php $canManage = auth()->user()->can('create', [\App\Models\TreatmentItem::class, $patient]); @endphp

    <div class="mb-4 flex flex-wrap items-center gap-2">
        @if ($canManage)
            <flux:button variant="primary" icon="plus" wire:click="openItemForm">{{ __('Add treatment') }}</flux:button>
            <flux:button icon="folder-plus" wire:click="openPlanForm">{{ __('New plan') }}</flux:button>
        @endif
        @if ($tooth)
            <flux:badge>{{ __('Tooth :number', ['number' => $tooth]) }} · {{ __(\App\Support\Fdi::name($tooth)) }}</flux:badge>
            <flux:button size="sm" variant="ghost" wire:click="$set('tooth', null)">{{ __('Show all teeth') }}</flux:button>
        @endif
    </div>

    @if ($addingPlan)
        <flux:card class="mb-6">
            <form wire:submit="addPlan" class="grid gap-4 sm:grid-cols-[1fr_16rem_auto] sm:items-end">
                <flux:input wire:model="plan.title" :label="__('Plan title')" :placeholder="__('e.g. Restorative treatment')" />
                <flux:select wire:model="plan.dentist_id" :label="__('Dentist')">
                    <flux:select.option value="">{{ __('Choose…') }}</flux:select.option>
                    @foreach ($this->dentists as $dentist)
                        <flux:select.option value="{{ $dentist->id }}">{{ $dentist->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <div class="flex gap-2">
                    <flux:button type="submit" variant="primary">{{ __('Create plan') }}</flux:button>
                    <flux:button wire:click="$set('addingPlan', false)">{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    @if ($addingItem)
        <flux:card class="mb-6">
            @if ($this->procedures->isEmpty())
                <flux:callout variant="warning" icon="exclamation-triangle">
                    <flux:callout.text>{{ __('The procedure catalogue is empty. An administrator can add procedures under “Procedure catalogue”.') }}</flux:callout.text>
                </flux:callout>
            @else
                <form wire:submit="addItem" class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-3">
                        <flux:select wire:model.live="item.procedure_id" :label="__('Procedure')">
                            <flux:select.option value="">{{ __('Choose…') }}</flux:select.option>
                            @foreach ($this->procedures as $procedure)
                                <flux:select.option value="{{ $procedure->id }}">{{ $procedure->localizedName() }} ({{ $procedure->code }})</flux:select.option>
                            @endforeach
                        </flux:select>
                        @if ($this->selectedProcedure?->scope !== 'mouth')
                            <flux:select wire:model.live="item.tooth_number" :label="__('Tooth (FDI)')">
                                <flux:select.option value="">{{ __('Choose…') }}</flux:select.option>
                                <optgroup label="{{ __('Permanent teeth') }}">
                                    @foreach (\App\Support\Fdi::permanent() as $number)
                                        <flux:select.option value="{{ $number }}">{{ $number }} · {{ __(\App\Support\Fdi::name($number)) }}</flux:select.option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="{{ __('Primary teeth') }}">
                                    @foreach (\App\Support\Fdi::primary() as $number)
                                        <flux:select.option value="{{ $number }}">{{ $number }} · {{ __(\App\Support\Fdi::name($number)) }}</flux:select.option>
                                    @endforeach
                                </optgroup>
                            </flux:select>
                        @endif
                        <flux:select wire:model="item.dentist_id" :label="__('Dentist')">
                            <flux:select.option value="">{{ __('Choose…') }}</flux:select.option>
                            @foreach ($this->dentists as $dentist)
                                <flux:select.option value="{{ $dentist->id }}">{{ $dentist->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    @if ($this->selectedProcedure?->scope === 'surface')
                        @if ($this->availableSurfaces === [])
                            <flux:text class="text-sm">{{ __('Choose a tooth to pick surfaces.') }}</flux:text>
                        @else
                            <flux:checkbox.group wire:model="item.surfaces" :label="__('Surfaces')" variant="buttons" class="flex flex-wrap gap-2">
                                @foreach ($this->availableSurfaces as $surface)
                                    <flux:checkbox value="{{ $surface->value }}" :label="$surface->value.' · '.$surface->label()" />
                                @endforeach
                            </flux:checkbox.group>
                        @endif
                        <flux:error name="item.surfaces" />
                    @endif

                    <div class="grid gap-4 sm:grid-cols-3">
                        <flux:select wire:model="item.treatment_plan_id" :label="__('Plan')">
                            <flux:select.option value="">{{ __('Not in a plan') }}</flux:select.option>
                            @foreach ($this->plans as $existingPlan)
                                @unless (in_array($existingPlan->status->value, ['completed', 'cancelled'], true))
                                    <flux:select.option value="{{ $existingPlan->id }}">{{ $existingPlan->title }}</flux:select.option>
                                @endunless
                            @endforeach
                        </flux:select>
                        <flux:input type="date" wire:model="item.planned_for" :label="__('Planned for')" />
                    </div>
                    <flux:textarea wire:model="item.notes" :label="__('Notes')" rows="2" dir="auto" />
                    <div class="flex gap-2">
                        <flux:button type="submit" variant="primary">{{ __('Add treatment') }}</flux:button>
                        <flux:button wire:click="$set('addingItem', false)">{{ __('Cancel') }}</flux:button>
                    </div>
                </form>
            @endif
        </flux:card>
    @endif

    @if ($this->items->isEmpty())
        <x-ui.empty-state :title="$tooth ? __('No treatments for this tooth') : __('No treatments yet')" />
    @else
        @php
            $groups = $this->items->groupBy(fn ($i) => $i->treatment_plan_id ?? 0);
            $planTitles = $this->plans->keyBy('id');
        @endphp
        <div class="space-y-6">
            @foreach ($groups as $planId => $groupItems)
                @php $groupPlan = $planTitles->get($planId); @endphp
                <div wire:key="group-{{ $planId }}">
                    <div class="mb-2 flex items-center gap-2">
                        <flux:heading size="lg">{{ $groupPlan ? $groupPlan->title : __('Not in a plan') }}</flux:heading>
                        @if ($groupPlan) <flux:badge size="sm">{{ $groupPlan->status->label() }}</flux:badge> @endif
                    </div>
                    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <table class="w-full text-start text-sm">
                            <thead class="bg-zinc-50 text-zinc-600 dark:bg-zinc-900 dark:text-zinc-400">
                                <tr>
                                    <th scope="col" class="px-4 py-2 text-start font-medium">{{ __('Tooth') }}</th>
                                    <th scope="col" class="px-4 py-2 text-start font-medium">{{ __('Procedure') }}</th>
                                    <th scope="col" class="px-4 py-2 text-start font-medium">{{ __('Dentist') }}</th>
                                    <th scope="col" class="px-4 py-2 text-start font-medium">{{ __('Status') }}</th>
                                    <th scope="col" class="px-4 py-2"><span class="sr-only">{{ __('Actions') }}</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach ($groupItems as $treatment)
                                    <tr wire:key="item-{{ $treatment->id }}">
                                        <td class="px-4 py-2" dir="ltr">
                                            {{ $treatment->tooth_number ?? __('Whole mouth') }}
                                            @if ($treatment->surfaces->isNotEmpty())
                                                <span class="text-xs text-zinc-500">{{ $treatment->surfaceCodes() }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2">
                                            {{ $treatment->procedure->localizedName() }}
                                            @if ($treatment->planned_for) <span class="block text-xs text-zinc-500">{{ __('Planned for :date', ['date' => $treatment->planned_for->format('Y-m-d')]) }}</span> @endif
                                        </td>
                                        <td class="px-4 py-2">{{ $treatment->dentist?->name }}</td>
                                        <td class="px-4 py-2"><x-ui.status-badge :status="$treatment->status" /></td>
                                        <td class="px-4 py-2 text-end">
                                            <div class="flex flex-wrap justify-end gap-1">
                                                @can('changeStatus', $treatment)
                                                    @if ($treatment->status->canTransitionTo(\App\Enums\TreatmentStatus::InProgress))
                                                        <flux:button size="xs" wire:click="changeStatus({{ $treatment->id }}, 'in_progress')">{{ __('Start') }}</flux:button>
                                                    @endif
                                                    <flux:button size="xs" variant="primary" wire:click="changeStatus({{ $treatment->id }}, 'completed')" wire:confirm="{{ __('Mark this treatment as completed?') }}">{{ __('Complete') }}</flux:button>
                                                    <flux:button size="xs" variant="ghost" wire:click="startCancel({{ $treatment->id }})">{{ __('Cancel') }}</flux:button>
                                                @endcan
                                                <flux:button size="xs" variant="ghost" wire:click="toggleHistory({{ $treatment->id }})">{{ __('History') }}</flux:button>
                                            </div>
                                        </td>
                                    </tr>
                                    @if ($cancellingId === $treatment->id)
                                        <tr>
                                            <td colspan="5" class="bg-zinc-50 px-4 py-3 dark:bg-zinc-900">
                                                <form wire:submit="confirmCancel" class="flex flex-wrap items-end gap-2">
                                                    <flux:input wire:model="cancelReason" :label="__('Reason for cancelling')" class="min-w-64 flex-1" />
                                                    <flux:button type="submit" variant="danger">{{ __('Cancel treatment') }}</flux:button>
                                                    <flux:button wire:click="$set('cancellingId', null)">{{ __('Keep it') }}</flux:button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endif
                                    @if ($historyId === $treatment->id)
                                        <tr>
                                            <td colspan="5" class="bg-zinc-50 px-4 py-3 dark:bg-zinc-900">
                                                @if ($treatment->notes) <p class="mb-2 whitespace-pre-line text-sm" dir="auto">{{ $treatment->notes }}</p> @endif
                                                <ol class="space-y-1 text-xs">
                                                    @foreach ($treatment->events()->with('performedBy:id,name')->orderBy('occurred_at')->orderBy('id')->get() as $event)
                                                        <li>
                                                            <span class="font-medium">{{ $event->occurred_at->format('Y-m-d H:i') }}</span>
                                                            · {{ $event->performedBy?->name }} ·
                                                            @if ($event->type === \App\Enums\TreatmentEventType::Created)
                                                                {{ __('Planned') }}
                                                            @else
                                                                {{ $event->from_status?->label() }} → {{ $event->to_status?->label() }}
                                                            @endif
                                                            @if ($event->note) <span class="block ps-4 text-zinc-600 dark:text-zinc-400" dir="auto">{{ $event->note }}</span> @endif
                                                        </li>
                                                    @endforeach
                                                </ol>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>
