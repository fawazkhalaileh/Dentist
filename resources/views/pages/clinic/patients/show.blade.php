<?php

use App\Actions\Patients\ArchivePatient;
use App\Actions\Patients\LinkPortalAccount;
use App\Enums\AppointmentStatus;
use App\Enums\TreatmentStatus;
use App\Models\Appointment;
use App\Models\ClinicalNote;
use App\Models\Patient;
use App\Models\TreatmentItem;
use App\Models\User;
use App\Support\Audit;
use App\Support\Fdi;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The patient record: one page with tabs, so staff never leave the patient to
 * look at another part of the record. Each tab embeds the matching component.
 */
new #[Title('Patient')] class extends Component {
    public Patient $patient;

    #[Url]
    public string $tab = 'overview';

    /** Tooth selected on the chart; filters the treatments tab. */
    #[Url]
    public ?int $tooth = null;

    public string $portalEmail = '';

    public function mount(Patient $patient): void
    {
        $this->authorize('view', $patient);
        $this->patient = $patient->load(['dentists:id,name', 'user:id,email']);
        $this->normaliseTab();

        // Who opened which record matters for health data; logged once per page load.
        Audit::record('patient.viewed', $patient);
    }

    /**
     * Tabs this user may open: [key => [label, ability]].
     *
     * @return array<string, string>
     */
    public function tabs(): array
    {
        $all = [
            'overview' => [__('Overview'), 'view'],
            'treatments' => [__('Chart & treatments'), 'viewClinical'],
            'history' => [__('Medical history'), 'viewClinical'],
            'notes' => [__('Notes'), 'viewClinical'],
            'documents' => [__('Documents'), 'viewClinical'],
            'appointments' => [__('Appointments'), 'view'],
            'billing' => [__('Billing'), 'billing'],
        ];

        $tabs = [];
        foreach ($all as $key => [$label, $ability]) {
            $allowed = $ability === 'billing'
                ? $this->user()->can(\App\Enums\Permission::BillingManage->value) && $this->user()->can('view', $this->patient)
                : $this->user()->can($ability, $this->patient);
            if ($allowed) {
                $tabs[$key] = $label;
            }
        }

        return $tabs;
    }

    public function updatedTab(): void
    {
        $this->normaliseTab();
    }

    private function normaliseTab(): void
    {
        if (! array_key_exists($this->tab, $this->tabs())) {
            $this->tab = 'overview';
        }
        if ($this->tooth !== null && ! Fdi::isValid($this->tooth)) {
            $this->tooth = null;
        }
    }

    /**
     * A tooth clicked on a chart: open (or stay on) the treatments tab for it.
     */
    #[On('tooth-selected')]
    public function toothSelected(?int $tooth = null): void
    {
        $this->tooth = $tooth !== null && Fdi::isValid($tooth) ? $tooth : null;
        if ($this->tooth !== null && $this->tab !== 'treatments' && array_key_exists('treatments', $this->tabs())) {
            $this->tab = 'treatments';
        }
    }

    #[On('tooth-cleared')]
    public function toothCleared(): void
    {
        $this->tooth = null;
    }

    public function canManageAppointments(): bool
    {
        return $this->user()->can('create', Appointment::class);
    }

    public function canSeeClinical(): bool
    {
        return $this->user()->can('viewClinical', $this->patient);
    }

    /**
     * Counts and dates for the overview dashboard. Clinical numbers only for clinical staff.
     *
     * @return array{completed: int, active: int, last_visit: ?Appointment, next: ?Appointment, last_note: ?ClinicalNote}
     */
    #[Computed]
    public function summary(): array
    {
        $clinical = $this->canSeeClinical();
        $items = $clinical ? $this->patient->treatmentItems()->toBase()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status') : collect();

        return [
            'completed' => (int) ($items[TreatmentStatus::Completed->value] ?? 0),
            'active' => (int) ($items[TreatmentStatus::Planned->value] ?? 0) + (int) ($items[TreatmentStatus::InProgress->value] ?? 0),
            'last_visit' => $this->patient->appointments()->with('dentist:id,name')
                ->whereIn('status', [AppointmentStatus::Completed->value, AppointmentStatus::CheckedIn->value])
                ->latest('starts_at')->first(),
            'next' => $this->patient->appointments()->with('dentist:id,name')
                ->where('starts_at', '>=', now())
                ->whereIn('status', [AppointmentStatus::Scheduled->value, AppointmentStatus::Confirmed->value])
                ->orderBy('starts_at')->first(),
            'last_note' => $clinical ? $this->patient->clinicalNotes()->with('author:id,name')->latest('id')->first() : null,
        ];
    }

    /**
     * Work in progress and planned, then the most recently completed treatments.
     *
     * @return Collection<int, TreatmentItem>
     */
    #[Computed]
    public function recentTreatments(): Collection
    {
        if (! $this->canSeeClinical()) {
            return new Collection;
        }

        $active = $this->patient->treatmentItems()->with(['procedure', 'surfaces'])
            ->whereIn('status', [TreatmentStatus::InProgress->value, TreatmentStatus::Planned->value])
            ->orderByRaw("FIELD(status, 'in_progress', 'planned')")->orderBy('planned_for')->limit(8)->get();
        $done = $this->patient->treatmentItems()->with(['procedure', 'surfaces'])
            ->where('status', TreatmentStatus::Completed->value)
            ->latest('completed_at')->limit(8)->get();

        return $active->concat($done);
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

    private function user(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}; ?>

<section class="w-full max-w-6xl">
    <x-ui.page-header :title="$patient->full_name" :description="$patient->patient_number.($patient->date_of_birth ? ' · '.trans_choice(':count year|:count years', (int) $patient->date_of_birth->diffInYears(now())) : '').($patient->phone ? ' · '.$patient->phone : '')">
        <x-slot:actions>
            @can('update', $patient)
                <flux:button icon="pencil-square" :href="route('clinic.patients.edit', $patient)" wire:navigate>{{ __('Edit details') }}</flux:button>
            @endcan
            @if ($this->canManageAppointments())
                <flux:button icon="calendar-days" :href="route('clinic.appointments.index', ['patient' => $patient->id])" wire:navigate>{{ __('Book appointment') }}</flux:button>
            @endif
            @can('delete', $patient)
                <flux:dropdown position="bottom" align="end">
                    <flux:button icon="ellipsis-horizontal" :aria-label="__('More actions')" />
                    <flux:menu>
                        <flux:menu.item icon="archive-box" variant="danger" wire:click="archive"
                                        wire:confirm="{{ __('Archive this patient? They disappear from lists and search. Nothing is deleted and the record can be restored.') }}">
                            {{ __('Archive patient') }}
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    @if (session('status'))
        <flux:callout variant="success" icon="check-circle" class="mb-4">
            <flux:callout.text>{{ session('status') }}</flux:callout.text>
        </flux:callout>
    @endif

    {{-- Allergies stay visible on every tab. --}}
    @if ($this->canSeeClinical())
        @php $medical = $patient->latestMedicalHistory; @endphp
        @if ($medical?->allergies)
            <flux:callout variant="danger" icon="exclamation-triangle" class="mb-4">
                <flux:callout.heading>{{ __('Allergies') }}</flux:callout.heading>
                <flux:callout.text><span class="whitespace-pre-line" dir="auto">{{ $medical->allergies }}</span></flux:callout.text>
            </flux:callout>
        @elseif (! $medical?->no_known_allergies)
            <flux:callout variant="warning" icon="exclamation-triangle" class="mb-4">
                <flux:callout.text>
                    {{ __('Allergies have not been recorded for this patient.') }}
                    <button type="button" wire:click="$set('tab', 'history')" class="underline">{{ __('Medical history') }}</button>
                </flux:callout.text>
            </flux:callout>
        @endif
    @endif

    <nav aria-label="{{ __('Patient record') }}" class="mb-6 border-b border-zinc-200 dark:border-zinc-700">
        <ul class="-mb-px flex flex-wrap gap-1" role="tablist">
            @foreach ($this->tabs() as $key => $label)
                <li role="presentation">
                    <button type="button" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}" wire:click="$set('tab', '{{ $key }}')"
                            @class(['rounded-t-md border-b-2 px-4 py-2 text-sm font-medium',
                                    'border-brand text-brand' => $tab === $key,
                                    'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200' => $tab !== $key])>
                        {{ $label }}
                    </button>
                </li>
            @endforeach
        </ul>
    </nav>

    <div role="tabpanel">
        @if ($tab === 'overview')
            @php $summary = $this->summary; @endphp
            {{-- Dashboard: what the patient has had done, what is planned, and when they come next. --}}
            <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @if ($this->canSeeClinical())
                    <button type="button" wire:click="$set('tab', 'treatments')" class="rounded-lg border border-zinc-200 p-4 text-start hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                        <p class="text-sm text-zinc-500">{{ __('Treatments done') }}</p>
                        <p class="text-2xl font-semibold">{{ $summary['completed'] }}</p>
                    </button>
                    <button type="button" wire:click="$set('tab', 'treatments')" class="rounded-lg border border-zinc-200 p-4 text-start hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                        <p class="text-sm text-zinc-500">{{ __('Planned / in progress') }}</p>
                        <p class="text-2xl font-semibold">{{ $summary['active'] }}</p>
                    </button>
                @endif
                <button type="button" wire:click="$set('tab', 'appointments')" class="rounded-lg border border-zinc-200 p-4 text-start hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                    <p class="text-sm text-zinc-500">{{ __('Last visit') }}</p>
                    <p class="font-semibold">{{ $summary['last_visit'] ? \App\Support\ClinicTime::local($summary['last_visit']->starts_at)->format('Y-m-d') : __('None yet') }}</p>
                    @if ($summary['last_visit']) <p class="text-xs text-zinc-500">{{ $summary['last_visit']->dentist?->name }}</p> @endif
                </button>
                <button type="button" wire:click="$set('tab', 'appointments')" class="rounded-lg border border-zinc-200 p-4 text-start hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                    <p class="text-sm text-zinc-500">{{ __('Next appointment') }}</p>
                    <p class="font-semibold" dir="ltr">{{ $summary['next'] ? \App\Support\ClinicTime::local($summary['next']->starts_at)->format('Y-m-d H:i') : __('None booked') }}</p>
                    @if ($summary['next']) <p class="text-xs text-zinc-500">{{ $summary['next']->dentist?->name }}</p> @endif
                </button>
            </div>

            @if ($this->canSeeClinical())
                <div class="mb-6">
                    <div class="mb-2 flex items-center justify-between">
                        <flux:heading size="lg">{{ __('Dental chart') }}</flux:heading>
                        <flux:text class="text-xs">{{ __('Select a tooth to see or add its treatments.') }}</flux:text>
                    </div>
                    <livewire:pages::clinic.patients.chart :patient="$patient" :embedded="true" wire:key="overview-chart" />
                </div>

                <div class="mb-6 grid gap-6 lg:grid-cols-[2fr_1fr]">
                    <flux:card class="space-y-3">
                        <div class="flex items-center justify-between">
                            <flux:heading size="lg">{{ __('Treatments') }}</flux:heading>
                            <flux:button size="sm" wire:click="$set('tab', 'treatments')">{{ __('Open chart & treatments') }}</flux:button>
                        </div>
                        @if ($this->recentTreatments->isEmpty())
                            <flux:text class="text-sm">{{ __('No treatments recorded yet.') }}</flux:text>
                        @else
                            <ul class="divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                                @foreach ($this->recentTreatments as $item)
                                    <li class="flex flex-wrap items-center justify-between gap-2 py-2" wire:key="ov-item-{{ $item->id }}">
                                        <span class="flex items-center gap-2">
                                            <span aria-hidden="true" class="inline-block size-3 shrink-0 rounded-sm" style="background-color: {{ $item->procedure->displayColor() }}"></span>
                                            <span>
                                                {{ $item->procedure->localizedName() }}
                                                <span class="text-zinc-500" dir="ltr">{{ $item->tooth_number ? '· '.$item->tooth_number.($item->surfaces->isNotEmpty() ? ' '.$item->surfaceCodes() : '') : '· '.__('Whole mouth') }}</span>
                                                <span class="block text-xs text-zinc-500">
                                                    @if ($item->completed_at) {{ __('Completed :date', ['date' => \App\Support\ClinicTime::local($item->completed_at)->format('Y-m-d')]) }}
                                                    @elseif ($item->planned_for) {{ __('Planned for :date', ['date' => $item->planned_for->format('Y-m-d')]) }} @endif
                                                </span>
                                            </span>
                                        </span>
                                        <x-ui.status-badge :status="$item->status" />
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </flux:card>

                    <flux:card class="space-y-3">
                        <div class="flex items-center justify-between">
                            <flux:heading size="lg">{{ __('Latest note') }}</flux:heading>
                            <flux:button size="sm" wire:click="$set('tab', 'notes')">{{ __('All notes') }}</flux:button>
                        </div>
                        @if ($summary['last_note'])
                            <p class="text-xs text-zinc-500">{{ $summary['last_note']->author?->name }} · {{ $summary['last_note']->created_at?->format('Y-m-d H:i') }}</p>
                            <p class="whitespace-pre-line text-sm" dir="auto">{{ \Illuminate\Support\Str::limit($summary['last_note']->body, 400) }}</p>
                        @else
                            <flux:text class="text-sm">{{ __('No clinical notes yet.') }}</flux:text>
                        @endif
                    </flux:card>
                </div>
            @endif

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

                <flux:card class="space-y-3">
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

                @can('update', $patient)
                    <flux:card class="space-y-3">
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
            </div>
        @elseif ($tab === 'treatments')
            <div class="mb-6">
                <livewire:pages::clinic.patients.chart :patient="$patient" :embedded="true" :selected="$tooth" wire:key="treatments-chart" />
            </div>
            <livewire:pages::clinic.patients.treatments :patient="$patient" :embedded="true" :tooth="$tooth" wire:key="treatments-list" />
        @elseif ($tab === 'history')
            <livewire:pages::clinic.patients.history :patient="$patient" :embedded="true" wire:key="tab-history" />
        @elseif ($tab === 'notes')
            <livewire:pages::clinic.patients.notes :patient="$patient" :embedded="true" wire:key="tab-notes" />
        @elseif ($tab === 'documents')
            <livewire:pages::clinic.patients.documents :patient="$patient" :embedded="true" wire:key="tab-documents" />
        @elseif ($tab === 'appointments')
            <livewire:pages::clinic.patients.appointments :patient="$patient" :embedded="true" wire:key="tab-appointments" />
        @elseif ($tab === 'billing')
            <livewire:pages::clinic.patients.billing :patient="$patient" :embedded="true" wire:key="tab-billing" />
        @endif
    </div>
</section>
