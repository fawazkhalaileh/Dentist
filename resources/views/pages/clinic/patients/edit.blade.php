<?php

use App\Actions\Patients\SavePatient;
use App\Enums\Role;
use App\Livewire\Forms\PatientForm;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Patient details')] class extends Component {
    public PatientForm $form;

    public ?Patient $patient = null;

    /** Set once staff confirm the new patient is not one of the possible duplicates. */
    public bool $confirmedNotDuplicate = false;

    /** @var list<int> */
    #[Locked]
    public array $duplicateIds = [];

    public function mount(?Patient $patient = null): void
    {
        $this->patient = $patient?->exists ? $patient : null;
        $this->authorizeForm();
        $this->form->load($this->patient);
    }

    private function authorizeForm(): void
    {
        $this->patient
            ? $this->authorize('update', $this->patient)
            : $this->authorize('create', Patient::class);
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function dentists(): Collection
    {
        return User::whereIn('id', $this->form->dentistOptionIds())->orderBy('name')->get(['id', 'name', 'is_active']);
    }

    /**
     * @return Collection<int, Patient>
     */
    #[Computed]
    public function duplicates(): Collection
    {
        return Patient::whereIn('id', $this->duplicateIds)->orderBy('last_name')->get();
    }

    public function updatedForm(): void
    {
        // Changing identifying details invalidates an earlier "not a duplicate" confirmation.
        $this->confirmedNotDuplicate = false;
        $this->duplicateIds = [];
    }

    public function save(SavePatient $save): void
    {
        $this->authorizeForm();
        // Form properties can be changed by the browser; the locked model is the source of truth.
        $this->form->patientId = $this->patient?->id;
        $this->form->validate();

        if ($this->patient === null && ! $this->confirmedNotDuplicate) {
            $this->duplicateIds = array_map('intval', $this->form->possibleDuplicates()->pluck('id')->all());
            if ($this->duplicateIds !== []) {
                return;
            }
        }

        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $patient = $save->handle($this->form->payload(), $this->form->dentistIds(), $user, $this->patient);

        session()->flash('status', $this->patient ? __('Patient details saved.') : __('Patient registered as :number.', ['number' => $patient->patient_number]));
        $this->redirectRoute('clinic.patients.show', $patient, navigate: true);
    }

    public function confirmNotDuplicate(SavePatient $save): void
    {
        $this->confirmedNotDuplicate = true;
        $this->save($save);
    }
}; ?>

<section class="w-full max-w-4xl">
    <x-ui.page-header :title="$patient ? __('Edit :name', ['name' => $patient->full_name]) : __('New patient')">
        <x-slot:actions>
            <flux:button :href="$patient ? route('clinic.patients.show', $patient) : route('clinic.patients.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($duplicateIds !== [])
        <flux:callout variant="warning" icon="exclamation-triangle" class="mb-6">
            <flux:callout.heading>{{ __('This patient may already be registered') }}</flux:callout.heading>
            <flux:callout.text>
                {{ __('These patients have the same phone number, or the same name and date of birth:') }}
                <ul class="mt-2 list-disc ps-5">
                    @foreach ($this->duplicates as $duplicate)
                        <li>
                            <a href="{{ route('clinic.patients.show', $duplicate) }}" class="underline" target="_blank">{{ $duplicate->full_name }}</a>
                            <span dir="ltr">({{ $duplicate->patient_number }}{{ $duplicate->date_of_birth ? ', '.$duplicate->date_of_birth->format('Y-m-d') : '' }})</span>
                        </li>
                    @endforeach
                </ul>
            </flux:callout.text>
            <x-slot:actions>
                <flux:button wire:click="confirmNotDuplicate">{{ __('It is a different person, register anyway') }}</flux:button>
            </x-slot:actions>
        </flux:callout>
    @endif

    <form wire:submit="save" class="space-y-6">
        <flux:card class="space-y-4">
            <flux:heading size="lg">{{ __('Personal details') }}</flux:heading>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model.blur="form.first_name" :label="__('First name')" required />
                <flux:input wire:model.blur="form.last_name" :label="__('Last name')" required />
                <flux:input wire:model.blur="form.date_of_birth" type="date" :label="__('Date of birth')" max="{{ now()->format('Y-m-d') }}" />
                <flux:select wire:model="form.sex" :label="__('Sex')">
                    <flux:select.option value="">{{ __('Not recorded') }}</flux:select.option>
                    <flux:select.option value="female">{{ __('Female') }}</flux:select.option>
                    <flux:select.option value="male">{{ __('Male') }}</flux:select.option>
                </flux:select>
                <flux:select wire:model="form.preferred_locale" :label="__('Preferred language')">
                    @foreach (\App\Support\Locales::supported() as $locale)
                        <flux:select.option value="{{ $locale }}">{{ __(config("clinic.locales.{$locale}.name")) }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </flux:card>

        <flux:card class="space-y-4">
            <flux:heading size="lg">{{ __('Contact details') }}</flux:heading>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model.blur="form.phone" type="tel" :label="__('Phone')" dir="ltr" />
                <flux:input wire:model.blur="form.email" type="email" :label="__('Email')" dir="ltr" />
                <flux:input wire:model="form.address_line" :label="__('Address')" class="sm:col-span-2" />
                <flux:input wire:model="form.city" :label="__('City')" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="form.emergency_contact_name" :label="__('Emergency contact name')" />
                <flux:input wire:model="form.emergency_contact_phone" type="tel" :label="__('Emergency contact phone')" dir="ltr" />
            </div>
        </flux:card>

        <flux:card class="space-y-4">
            <flux:heading size="lg">{{ __('Care team') }}</flux:heading>
            <flux:text class="text-sm">{{ __('Dentists on the care team can see this patient\'s clinical record. Dentists who see the patient for an appointment or treatment are added automatically.') }}</flux:text>
            @if ($this->dentists->isEmpty())
                <flux:text class="text-sm">{{ __('No dentists have been added yet.') }}</flux:text>
            @else
                <flux:checkbox.group wire:model="form.dentist_ids">
                    @foreach ($this->dentists as $dentist)
                        <flux:checkbox value="{{ $dentist->id }}" :label="$dentist->name.($dentist->is_active ? '' : ' ('.__('deactivated').')')" />
                    @endforeach
                </flux:checkbox.group>
            @endif
            <flux:error name="form.dentist_ids" />
            <flux:error name="form.dentist_ids.*" />
        </flux:card>

        <flux:button type="submit" variant="primary">{{ $patient ? __('Save changes') : __('Register patient') }}</flux:button>
    </form>
</section>
