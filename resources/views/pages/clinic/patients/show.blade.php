<?php

use App\Actions\Patients\ArchivePatient;
use App\Models\Patient;
use App\Support\Audit;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Patient')] class extends Component {
    public Patient $patient;

    public function mount(Patient $patient): void
    {
        $this->authorize('view', $patient);
        $this->patient = $patient->load(['dentists:id,name', 'user:id,email']);

        // Who opened which record matters for health data; logged once per page load.
        Audit::record('patient.viewed', $patient);
    }

    public function archive(ArchivePatient $archive): void
    {
        $this->authorize('delete', $this->patient);

        $archive->archive($this->patient);
        session()->flash('status', __(':name was archived. An administrator can restore the record from the patient list.', ['name' => $this->patient->full_name]));
        $this->redirectRoute('clinic.patients.index', navigate: true);
    }
}; ?>

<section class="w-full max-w-5xl">
    <x-ui.page-header :title="$patient->full_name">
        <x-slot:actions>
            @can('update', $patient)
                <flux:button icon="pencil-square" :href="route('clinic.patients.edit', $patient)" wire:navigate>{{ __('Edit details') }}</flux:button>
            @endcan
            @can('delete', $patient)
                <flux:button variant="danger" wire:click="archive"
                             wire:confirm="{{ __('Archive this patient? They disappear from lists and search. Nothing is deleted and the record can be restored.') }}">
                    {{ __('Archive') }}
                </flux:button>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle" class="mb-6">
            <flux:callout.text>{{ session('status') }}</flux:callout.text>
        </flux:callout>
    @endif

    @php
        $sections = [
            ['label' => __('Appointments'), 'route' => 'clinic.patients.appointments', 'ability' => 'view'],
            ['label' => __('Medical and dental history'), 'route' => 'clinic.patients.history', 'ability' => 'viewClinical'],
            ['label' => __('Dental chart'), 'route' => 'clinic.patients.chart', 'ability' => 'viewClinical'],
            ['label' => __('Treatment plans'), 'route' => 'clinic.patients.treatment-plans', 'ability' => 'viewClinical'],
            ['label' => __('Clinical notes'), 'route' => 'clinic.patients.notes', 'ability' => 'viewClinical'],
            ['label' => __('Documents'), 'route' => 'clinic.patients.documents', 'ability' => 'viewClinical'],
        ];
    @endphp
    <nav aria-label="{{ __('Patient record') }}" class="mb-6 flex flex-wrap gap-2">
        @foreach ($sections as $section)
            @can($section['ability'], $patient)
                <flux:button size="sm" :href="route($section['route'], $patient)" wire:navigate>{{ $section['label'] }}</flux:button>
            @endcan
        @endforeach
    </nav>

    <div class="grid gap-6 md:grid-cols-2">
        <flux:card class="space-y-3">
            <flux:heading size="lg">{{ __('Personal details') }}</flux:heading>
            <dl class="grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 text-sm">
                <dt class="text-zinc-500">{{ __('Patient number') }}</dt>
                <dd dir="ltr" class="text-start">{{ $patient->patient_number }}</dd>
                <dt class="text-zinc-500">{{ __('Date of birth') }}</dt>
                <dd>
                    @if ($patient->date_of_birth)
                        {{ $patient->date_of_birth->format('Y-m-d') }}
                        <span class="text-zinc-500">({{ trans_choice(':count year|:count years', (int) $patient->date_of_birth->diffInYears(now())) }})</span>
                    @else
                        <span class="text-zinc-500">{{ __('Not recorded') }}</span>
                    @endif
                </dd>
                <dt class="text-zinc-500">{{ __('Sex') }}</dt>
                <dd>{{ match ($patient->sex) { 'female' => __('Female'), 'male' => __('Male'), default => __('Not recorded') } }}</dd>
                <dt class="text-zinc-500">{{ __('Preferred language') }}</dt>
                <dd>{{ __(config("clinic.locales.{$patient->preferred_locale}.name", $patient->preferred_locale)) }}</dd>
                <dt class="text-zinc-500">{{ __('Portal account') }}</dt>
                <dd>{{ $patient->user ? __('Yes') : __('No') }}</dd>
            </dl>
        </flux:card>

        <flux:card class="space-y-3">
            <flux:heading size="lg">{{ __('Contact') }}</flux:heading>
            <dl class="grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 text-sm">
                <dt class="text-zinc-500">{{ __('Phone') }}</dt>
                <dd>@if ($patient->phone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $patient->phone) }}" class="underline" dir="ltr">{{ $patient->phone }}</a>@else<span class="text-zinc-500">-</span>@endif</dd>
                <dt class="text-zinc-500">{{ __('Email') }}</dt>
                <dd>@if ($patient->email)<a href="mailto:{{ $patient->email }}" class="underline" dir="ltr">{{ $patient->email }}</a>@else<span class="text-zinc-500">-</span>@endif</dd>
                <dt class="text-zinc-500">{{ __('Address') }}</dt>
                <dd>{{ collect([$patient->address_line, $patient->city])->filter()->implode(', ') ?: '-' }}</dd>
                <dt class="text-zinc-500">{{ __('Emergency contact') }}</dt>
                <dd>
                    {{ $patient->emergency_contact_name ?: '-' }}
                    @if ($patient->emergency_contact_phone) <span dir="ltr">{{ $patient->emergency_contact_phone }}</span> @endif
                </dd>
            </dl>
        </flux:card>

        <flux:card class="space-y-3 md:col-span-2">
            <flux:heading size="lg">{{ __('Care team') }}</flux:heading>
            @if ($patient->dentists->isEmpty())
                <flux:text class="text-sm">{{ __('No dentist assigned yet.') }}</flux:text>
            @else
                <ul class="flex flex-wrap gap-2">
                    @foreach ($patient->dentists as $dentist)
                        <li><flux:badge>{{ $dentist->name }}</flux:badge></li>
                    @endforeach
                </ul>
            @endif
        </flux:card>
    </div>
</section>
