<?php

use App\Actions\Patients\ArchivePatient;
use App\Actions\Patients\LinkPortalAccount;
use App\Models\Patient;
use App\Support\Audit;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Patient')] class extends Component {
    public Patient $patient;

    public string $portalEmail = '';

    public function mount(Patient $patient): void
    {
        $this->authorize('view', $patient);
        $this->patient = $patient->load(['dentists:id,name', 'user:id,email']);

        // Who opened which record matters for health data; logged once per page load.
        Audit::record('patient.viewed', $patient);
    }

    public function linkPortal(LinkPortalAccount $link): void
    {
        $this->authorize('update', $this->patient);
        $this->validate(['portalEmail' => ['required', 'email', 'max:255']], [], ['portalEmail' => __('email')]);

        $link->link($this->patient, $this->portalEmail);
        $this->portalEmail = '';
        $this->patient->load('user:id,email');
        \Flux\Flux::toast(variant: 'success', text: __('Portal account linked.'));
    }

    public function unlinkPortal(LinkPortalAccount $link): void
    {
        $this->authorize('update', $this->patient);

        $link->unlink($this->patient);
        $this->patient->load('user:id,email');
        \Flux\Flux::toast(variant: 'success', text: __('Portal account unlinked.'));
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

    @can('viewClinical', $patient)
        @php $medical = $patient->latestMedicalHistory; @endphp
        @if ($medical?->allergies)
            <flux:callout variant="danger" icon="exclamation-triangle" class="mb-6">
                <flux:callout.heading>{{ __('Allergies') }}</flux:callout.heading>
                <flux:callout.text><span class="whitespace-pre-line" dir="auto">{{ $medical->allergies }}</span></flux:callout.text>
            </flux:callout>
        @elseif (! $medical?->no_known_allergies)
            <flux:callout variant="warning" icon="exclamation-triangle" class="mb-6">
                <flux:callout.text>
                    {{ __('Allergies have not been recorded for this patient.') }}
                    <a href="{{ route('clinic.patients.history', $patient) }}" wire:navigate class="underline">{{ __('Medical history') }}</a>
                </flux:callout.text>
            </flux:callout>
        @endif
    @endcan

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

        @can('update', $patient)
            <flux:card class="space-y-3 md:col-span-2">
                <flux:heading size="lg">{{ __('Patient portal') }}</flux:heading>
                @if ($patient->user)
                    <div class="flex flex-wrap items-center gap-3 text-sm">
                        <span>{{ __('Linked to :email.', ['email' => $patient->user->email]) }}</span>
                        <flux:button size="xs" variant="ghost" wire:click="unlinkPortal" wire:confirm="{{ __('Unlink this portal account? The patient will no longer see their appointments online.') }}">{{ __('Unlink') }}</flux:button>
                    </div>
                @else
                    <flux:text class="text-sm">{{ __('Patients register on the website, then reception links the account here after checking their identity (for example with an ID card).') }}</flux:text>
                    <form wire:submit="linkPortal" class="flex flex-wrap items-end gap-2">
                        <flux:input wire:model="portalEmail" type="email" :label="__('Portal account email')" dir="ltr" class="min-w-64" />
                        <flux:button type="submit">{{ __('Link account') }}</flux:button>
                    </form>
                @endif
            </flux:card>
        @endcan

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
