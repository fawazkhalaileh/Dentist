<?php

use App\Actions\Patients\RecordHistory;
use App\Models\DentalHistory;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\User;
use App\Support\Audit;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Medical and dental history')] class extends Component {
    public Patient $patient;

    /** Inside the patient page: no page header. */
    #[Locked]
    public bool $embedded = false;

    /** Which review form is open: '', 'medical' or 'dental'. */
    #[Locked]
    public string $editing = '';

    /** Earlier version shown in full, if any. */
    #[Locked]
    public ?string $viewing = null;

    /** @var array{allergies: string, no_known_allergies: bool, medications: string, conditions: string, notes: string} */
    public array $medical = ['allergies' => '', 'no_known_allergies' => false, 'medications' => '', 'conditions' => '', 'notes' => ''];

    /** @var array{chief_complaint: string, previous_treatments: string, notes: string} */
    public array $dental = ['chief_complaint' => '', 'previous_treatments' => '', 'notes' => ''];

    public function mount(Patient $patient, bool $embedded = false): void
    {
        $this->embedded = $embedded;
        $this->authorize('viewClinical', $patient);
        $this->patient = $patient;

        Audit::record('clinical.history_viewed', $patient);
    }

    #[Computed]
    public function currentMedical(): ?MedicalHistory
    {
        return $this->patient->medicalHistories()->with('recordedBy:id,name')->latest('id')->first();
    }

    #[Computed]
    public function currentDental(): ?DentalHistory
    {
        return $this->patient->dentalHistories()->with('recordedBy:id,name')->latest('id')->first();
    }

    /**
     * @return Collection<int, MedicalHistory>
     */
    #[Computed]
    public function medicalVersions(): Collection
    {
        return $this->patient->medicalHistories()->with('recordedBy:id,name')->latest('id')->get();
    }

    /**
     * @return Collection<int, DentalHistory>
     */
    #[Computed]
    public function dentalVersions(): Collection
    {
        return $this->patient->dentalHistories()->with('recordedBy:id,name')->latest('id')->get();
    }

    public function startReview(string $type): void
    {
        $this->authorize('writeClinical', $this->patient);
        abort_unless(in_array($type, ['medical', 'dental'], true), 404);
        $this->resetValidation();

        // Start from the current version, so a review only changes what changed.
        if ($type === 'medical') {
            $current = $this->currentMedical;
            $this->medical = [
                'allergies' => (string) $current?->allergies,
                'no_known_allergies' => (bool) $current?->no_known_allergies,
                'medications' => (string) $current?->medications,
                'conditions' => (string) $current?->conditions,
                'notes' => (string) $current?->notes,
            ];
        } else {
            $current = $this->currentDental;
            $this->dental = [
                'chief_complaint' => (string) $current?->chief_complaint,
                'previous_treatments' => (string) $current?->previous_treatments,
                'notes' => (string) $current?->notes,
            ];
        }

        $this->editing = $type;
    }

    public function cancel(): void
    {
        $this->editing = '';
        $this->resetValidation();
    }

    public function saveMedical(RecordHistory $record): void
    {
        $this->authorize('writeClinical', $this->patient);

        $this->validate([
            'medical.allergies' => ['nullable', 'string', 'max:2000', 'prohibited_if_accepted:medical.no_known_allergies'],
            'medical.no_known_allergies' => ['boolean'],
            'medical.medications' => ['nullable', 'string', 'max:2000'],
            'medical.conditions' => ['nullable', 'string', 'max:2000'],
            'medical.notes' => ['nullable', 'string', 'max:5000'],
        ], [
            'medical.allergies.prohibited_if_accepted' => __('Clear the allergies, or untick “No known allergies”.'),
        ]);

        $record->medical($this->patient, [
            'allergies' => $this->clean($this->medical['allergies']),
            'no_known_allergies' => (bool) $this->medical['no_known_allergies'],
            'medications' => $this->clean($this->medical['medications']),
            'conditions' => $this->clean($this->medical['conditions']),
            'notes' => $this->clean($this->medical['notes']),
        ], $this->actor());

        $this->editing = '';
        unset($this->currentMedical, $this->medicalVersions);
        Flux::toast(variant: 'success', text: __('Medical history recorded.'));
    }

    public function saveDental(RecordHistory $record): void
    {
        $this->authorize('writeClinical', $this->patient);

        $this->validate([
            'dental.chief_complaint' => ['nullable', 'string', 'max:2000'],
            'dental.previous_treatments' => ['nullable', 'string', 'max:2000'],
            'dental.notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $record->dental($this->patient, [
            'chief_complaint' => $this->clean($this->dental['chief_complaint']),
            'previous_treatments' => $this->clean($this->dental['previous_treatments']),
            'notes' => $this->clean($this->dental['notes']),
        ], $this->actor());

        $this->editing = '';
        unset($this->currentDental, $this->dentalVersions);
        Flux::toast(variant: 'success', text: __('Dental history recorded.'));
    }

    public function showVersion(string $type, int $id): void
    {
        abort_unless(in_array($type, ['medical', 'dental'], true), 404);
        $versions = $type === 'medical' ? $this->medicalVersions : $this->dentalVersions;
        abort_unless($versions->contains('id', $id), 404);

        $this->viewing = $this->viewing === "{$type}:{$id}" ? null : "{$type}:{$id}";
    }

    private function clean(string $value): ?string
    {
        return trim($value) === '' ? null : trim($value);
    }

    private function actor(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}; ?>

<section @class(['w-full', 'max-w-5xl' => ! $embedded])>
    @unless ($embedded)
        <x-ui.page-header :title="__('Medical and dental history')" :description="$patient->full_name.' · '.$patient->patient_number">
            <x-slot:actions>
                <flux:button :href="route('clinic.patients.show', $patient)" wire:navigate>{{ __('Open patient record') }}</flux:button>
            </x-slot:actions>
        </x-ui.page-header>
    @endunless

    @php $canWrite = auth()->user()->can('writeClinical', $patient); @endphp

    <div class="space-y-6">
        {{-- Medical history --}}
        <flux:card class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <flux:heading size="lg">{{ __('Medical history') }}</flux:heading>
                @if ($canWrite && $editing !== 'medical')
                    <flux:button size="sm" icon="pencil-square" wire:click="startReview('medical')">{{ $this->currentMedical ? __('Record a review') : __('Record medical history') }}</flux:button>
                @endif
            </div>

            @if ($editing === 'medical')
                <form wire:submit="saveMedical" class="space-y-4">
                    <flux:textarea wire:model="medical.allergies" :label="__('Allergies')" rows="2" :description="__('Medicines, latex, materials, foods. Include the reaction if known.')" />
                    <flux:checkbox wire:model="medical.no_known_allergies" :label="__('No known allergies (patient was asked)')" />
                    <flux:textarea wire:model="medical.medications" :label="__('Current medications')" rows="2" />
                    <flux:textarea wire:model="medical.conditions" :label="__('Medical conditions')" rows="2" :description="__('For example diabetes, heart conditions, bleeding disorders, pregnancy.')" />
                    <flux:textarea wire:model="medical.notes" :label="__('Notes')" rows="3" />
                    <flux:text class="text-sm">{{ __('Saving adds a new version. Earlier versions are kept unchanged.') }}</flux:text>
                    <div class="flex gap-2">
                        <flux:button type="submit" variant="primary">{{ __('Save medical history') }}</flux:button>
                        <flux:button wire:click="cancel">{{ __('Cancel') }}</flux:button>
                    </div>
                </form>
            @elseif ($current = $this->currentMedical)
                @include('clinic.patients.partials.medical-history', ['history' => $current])
                <flux:text class="text-xs">{{ __('Recorded :date by :name.', ['date' => $current->created_at?->format('Y-m-d H:i'), 'name' => $current->recordedBy?->name]) }}</flux:text>
            @else
                <flux:callout variant="warning" icon="exclamation-triangle">
                    <flux:callout.text>{{ __('No medical history has been recorded. Allergies are unknown.') }}</flux:callout.text>
                </flux:callout>
            @endif
        </flux:card>

        {{-- Dental history --}}
        <flux:card class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <flux:heading size="lg">{{ __('Dental history') }}</flux:heading>
                @if ($canWrite && $editing !== 'dental')
                    <flux:button size="sm" icon="pencil-square" wire:click="startReview('dental')">{{ $this->currentDental ? __('Record a review') : __('Record dental history') }}</flux:button>
                @endif
            </div>

            @if ($editing === 'dental')
                <form wire:submit="saveDental" class="space-y-4">
                    <flux:textarea wire:model="dental.chief_complaint" :label="__('Main complaint')" rows="2" />
                    <flux:textarea wire:model="dental.previous_treatments" :label="__('Previous dental treatment')" rows="2" />
                    <flux:textarea wire:model="dental.notes" :label="__('Notes')" rows="3" />
                    <div class="flex gap-2">
                        <flux:button type="submit" variant="primary">{{ __('Save dental history') }}</flux:button>
                        <flux:button wire:click="cancel">{{ __('Cancel') }}</flux:button>
                    </div>
                </form>
            @elseif ($current = $this->currentDental)
                @include('clinic.patients.partials.dental-history', ['history' => $current])
                <flux:text class="text-xs">{{ __('Recorded :date by :name.', ['date' => $current->created_at?->format('Y-m-d H:i'), 'name' => $current->recordedBy?->name]) }}</flux:text>
            @else
                <flux:text class="text-sm">{{ __('No dental history has been recorded.') }}</flux:text>
            @endif
        </flux:card>

        {{-- Earlier versions --}}
        @if ($this->medicalVersions->count() > 1 || $this->dentalVersions->count() > 1)
            <flux:card class="space-y-3">
                <flux:heading size="lg">{{ __('Earlier versions') }}</flux:heading>
                <ul class="divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                    @foreach ([['medical', $this->medicalVersions, __('Medical history')], ['dental', $this->dentalVersions, __('Dental history')]] as [$type, $versions, $label])
                        @foreach ($versions->skip(1) as $version)
                            <li class="py-2" wire:key="{{ $type }}-{{ $version->id }}">
                                <button type="button" wire:click="showVersion('{{ $type }}', {{ $version->id }})" class="text-start underline">
                                    {{ $label }} · {{ $version->created_at?->format('Y-m-d H:i') }} · {{ $version->recordedBy?->name }}
                                </button>
                                @if ($viewing === "{$type}:{$version->id}")
                                    <div class="mt-3 rounded-md bg-zinc-50 p-3 dark:bg-zinc-900">
                                        @include("clinic.patients.partials.{$type}-history", ['history' => $version])
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    @endforeach
                </ul>
            </flux:card>
        @endif
    </div>
</section>
