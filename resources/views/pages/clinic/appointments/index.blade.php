<?php

use App\Actions\Scheduling\BookAppointment;
use App\Actions\Scheduling\ChangeAppointmentStatus;
use App\Enums\AppointmentStatus;
use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use App\Support\Availability;
use App\Support\ClinicTime;
use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Appointments')] class extends Component {
    #[Url]
    public string $date = '';

    public bool $booking = false;

    /** Appointment being moved, if the booking form is in "reschedule" mode. */
    #[Locked]
    public ?int $movingId = null;

    /** @var array{patient_id: ?int, dentist_id: ?int, service_id: ?int, duration: int, date: string, time: string, notes: string, outside_hours: bool} */
    public array $form = ['patient_id' => null, 'dentist_id' => null, 'service_id' => null, 'duration' => 30, 'date' => '', 'time' => '', 'notes' => '', 'outside_hours' => false];

    public string $patientSearch = '';

    #[Locked]
    public ?int $cancellingId = null;

    public string $cancelReason = '';

    public function boot(): void
    {
        $this->authorize('viewAny', Appointment::class);
    }

    public function mount(): void
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->date) || ! strtotime($this->date)) {
            $this->date = ClinicTime::today();
        }
    }

    public function canManage(): bool
    {
        return $this->user()->can(Permission::AppointmentsManage->value);
    }

    /**
     * Dentists shown as columns: everyone for front-desk staff, yourself for a dentist.
     *
     * @return Collection<int, User>
     */
    #[Computed]
    public function dentists(): Collection
    {
        $user = $this->user();
        if (! $user->can(Permission::AppointmentsViewAll->value)) {
            return new Collection([$user]);
        }

        return User::havingRole(Role::Dentist)->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    /**
     * @return Collection<int, Appointment>
     */
    #[Computed]
    public function appointments(): Collection
    {
        $start = ClinicTime::toUtc($this->date, '00:00');

        return Appointment::with(['patient:id,first_name,last_name,patient_number,phone', 'service.translations'])
            ->whereIn('dentist_id', $this->dentists->pluck('id'))
            ->where('starts_at', '>=', $start)->where('starts_at', '<', $start->addDay())
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * @return Collection<int, Service>
     */
    #[Computed]
    public function services(): Collection
    {
        return Service::with('translations')->orderBy('sort_order')->get();
    }

    /**
     * @return Collection<int, Patient>
     */
    #[Computed]
    public function patientResults(): Collection
    {
        $term = trim($this->patientSearch);
        if (mb_strlen($term) < 2 || $this->form['patient_id']) {
            return new Collection;
        }
        $like = '%'.addcslashes($term, '%_\\').'%';
        $digits = ltrim(preg_replace('/\D+/', '', $term) ?? '', '0');

        return Patient::query()->where(function (Builder $q) use ($like, $digits) {
            $q->where('patient_number', 'like', $like)
                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$like])
                ->orWhereRaw("CONCAT(last_name, ' ', first_name) LIKE ?", [$like]);
            if (strlen($digits) >= 3) {
                $q->orWhereRaw("REGEXP_REPLACE(phone, '[^0-9]', '') LIKE ?", ['%'.$digits.'%']);
            }
        })->orderBy('last_name')->limit(8)->get();
    }

    #[Computed]
    public function selectedPatient(): ?Patient
    {
        return $this->form['patient_id'] ? Patient::find($this->form['patient_id']) : null;
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function freeSlots(): array
    {
        $dentist = $this->form['dentist_id'] ? $this->dentists->firstWhere('id', (int) $this->form['dentist_id']) : null;
        if (! $dentist || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->form['date'])) {
            return [];
        }

        return Availability::freeSlots($dentist, $this->form['date'], max(5, (int) $this->form['duration']), $this->movingId);
    }

    public function updatedDate(): void
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->date) || ! strtotime($this->date)) {
            $this->date = ClinicTime::today();
        }
        unset($this->appointments);
    }

    public function shiftDay(int $days): void
    {
        $this->date = CarbonImmutable::createFromFormat('Y-m-d', $this->date)->addDays(max(-31, min(31, $days)))->format('Y-m-d');
        unset($this->appointments);
    }

    public function today(): void
    {
        $this->date = ClinicTime::today();
        unset($this->appointments);
    }

    public function openBooking(?int $dentistId = null): void
    {
        $this->authorize('create', Appointment::class);
        $this->resetValidation();
        $this->movingId = null;
        $this->patientSearch = '';
        $this->form = ['patient_id' => null, 'dentist_id' => $dentistId ?? $this->dentists->first()?->id, 'service_id' => null, 'duration' => 30, 'date' => $this->date, 'time' => '', 'notes' => '', 'outside_hours' => false];
        $this->booking = true;
    }

    public function openReschedule(int $id): void
    {
        $appointment = $this->visibleAppointment($id);
        $this->authorize('update', $appointment);
        abort_if($appointment->status->isFinal(), 422);

        $local = ClinicTime::local($appointment->starts_at);
        $this->resetValidation();
        $this->movingId = $appointment->id;
        $this->form = [
            'patient_id' => $appointment->patient_id,
            'dentist_id' => $appointment->dentist_id,
            'service_id' => $appointment->service_id,
            'duration' => (int) $appointment->starts_at->diffInMinutes($appointment->ends_at),
            'date' => $local->format('Y-m-d'),
            'time' => $local->format('H:i'),
            'notes' => (string) $appointment->notes,
            'outside_hours' => false,
        ];
        $this->booking = true;
    }

    public function pickPatient(int $id): void
    {
        $this->form['patient_id'] = Patient::findOrFail($id)->id;
        $this->patientSearch = '';
    }

    public function clearPatient(): void
    {
        abort_if($this->movingId !== null, 422);
        $this->form['patient_id'] = null;
    }

    public function updatedFormServiceId(): void
    {
        $service = $this->form['service_id'] ? Service::find($this->form['service_id']) : null;
        if ($service) {
            $this->form['duration'] = $service->duration_minutes;
        }
    }

    public function book(BookAppointment $book): void
    {
        $moving = $this->movingId ? $this->visibleAppointment($this->movingId) : null;
        $moving ? $this->authorize('update', $moving) : $this->authorize('create', Appointment::class);

        $this->validate([
            'form.patient_id' => ['required', 'integer', 'exists:patients,id'],
            'form.dentist_id' => ['required', 'integer', Rule::in($this->dentists->pluck('id')->all())],
            'form.service_id' => ['nullable', 'integer', 'exists:services,id'],
            'form.duration' => ['required', 'integer', 'min:5', 'max:480'],
            'form.date' => ['required', 'date_format:Y-m-d'],
            'form.time' => ['required', 'date_format:H:i'],
            'form.notes' => ['nullable', 'string', 'max:1000'],
            'form.outside_hours' => ['boolean'],
        ], ['form.patient_id.required' => __('Choose a patient.'), 'form.time.required' => __('Choose a time.')]);

        $starts = ClinicTime::toUtc($this->form['date'], $this->form['time']);

        try {
            $appointment = $book->handle(
                $moving?->patient ?? Patient::findOrFail($this->form['patient_id']),
                User::findOrFail($this->form['dentist_id']),
                $starts,
                $starts->addMinutes((int) $this->form['duration']),
                ['service_id' => $this->form['service_id'] ?: null, 'notes' => trim($this->form['notes']) ?: null, 'source' => 'staff'],
                $this->user(),
                (bool) $this->form['outside_hours'],
                $moving,
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->addError('form.time', collect($e->errors())->flatten()->first());

            return;
        }

        $this->booking = false;
        $this->date = ClinicTime::local($appointment->starts_at)->format('Y-m-d');
        $this->movingId = null;
        unset($this->appointments);
        Flux::toast(variant: 'success', text: $moving ? __('Appointment moved.') : __('Appointment booked.'));
    }

    public function setStatus(int $id, string $status, ChangeAppointmentStatus $change): void
    {
        $appointment = $this->visibleAppointment($id);
        $this->authorize('update', $appointment);
        $to = AppointmentStatus::from($status);
        abort_if($to === AppointmentStatus::Cancelled, 422);

        $change->handle($appointment, $to, $this->user());
        unset($this->appointments);
    }

    public function startCancel(int $id): void
    {
        $this->authorize('cancel', $this->visibleAppointment($id));
        $this->cancellingId = $id;
        $this->cancelReason = '';
        $this->resetValidation();
    }

    public function confirmCancel(ChangeAppointmentStatus $change): void
    {
        abort_if($this->cancellingId === null, 422);
        $appointment = $this->visibleAppointment($this->cancellingId);
        $this->authorize('cancel', $appointment);
        $this->validate(['cancelReason' => ['required', 'string', 'max:255']], [], ['cancelReason' => __('reason')]);

        $change->handle($appointment, AppointmentStatus::Cancelled, $this->user(), $this->cancelReason);
        $this->cancellingId = null;
        unset($this->appointments);
        Flux::toast(variant: 'success', text: __('Appointment cancelled.'));
    }

    /**
     * An appointment of one of the dentists this user may see.
     */
    private function visibleAppointment(int $id): Appointment
    {
        return Appointment::whereIn('dentist_id', $this->dentists->pluck('id'))->findOrFail($id);
    }

    private function user(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}; ?>

<section class="w-full">
    @php
        $statusColors = ['scheduled' => 'zinc', 'confirmed' => 'sky', 'checked_in' => 'amber', 'completed' => 'green', 'cancelled' => 'red', 'no_show' => 'red'];
        $day = \Carbon\CarbonImmutable::createFromFormat('Y-m-d', $date);
    @endphp

    <x-ui.page-header :title="__('Appointments')" :description="__('Times are in the clinic\'s time zone (:zone).', ['zone' => \App\Support\ClinicTime::zone()])">
        <x-slot:actions>
            @if ($this->canManage())
                <flux:button variant="primary" icon="plus" wire:click="openBooking">{{ __('New appointment') }}</flux:button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <flux:button size="sm" icon="chevron-left" wire:click="shiftDay(-1)" :aria-label="__('Previous day')" />
        <flux:button size="sm" wire:click="today">{{ __('Today') }}</flux:button>
        <flux:button size="sm" icon="chevron-right" wire:click="shiftDay(1)" :aria-label="__('Next day')" />
        <flux:input type="date" size="sm" wire:model.live="date" class="w-44" :aria-label="__('Date')" />
        <span class="font-medium">{{ $day->translatedFormat('l j F Y') }}</span>
    </div>

    @if ($booking)
        <flux:card class="mb-6">
            <form wire:submit="book" class="space-y-4">
                <flux:heading size="lg">{{ $movingId ? __('Move appointment') : __('New appointment') }}</flux:heading>

                <div>
                    @if ($this->selectedPatient)
                        <div class="flex items-center gap-2">
                            <span class="text-sm">{{ __('Patient') }}:</span>
                            <flux:badge>{{ $this->selectedPatient->full_name }} · {{ $this->selectedPatient->patient_number }}</flux:badge>
                            @unless ($movingId)
                                <flux:button size="xs" variant="ghost" wire:click="clearPatient">{{ __('Change') }}</flux:button>
                            @endunless
                        </div>
                    @else
                        <flux:input wire:model.live.debounce.300ms="patientSearch" :label="__('Patient')" :placeholder="__('Search by name, number or phone')" icon="magnifying-glass" />
                        @if ($this->patientResults->isNotEmpty())
                            <ul class="mt-1 divide-y divide-zinc-200 rounded-md border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
                                @foreach ($this->patientResults as $result)
                                    <li><button type="button" wire:click="pickPatient({{ $result->id }})" class="w-full px-3 py-2 text-start text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800">{{ $result->full_name }} <span class="text-zinc-500" dir="ltr">· {{ $result->patient_number }} · {{ $result->phone }}</span></button></li>
                                @endforeach
                            </ul>
                        @elseif (mb_strlen(trim($patientSearch)) >= 2)
                            <p class="mt-1 text-sm text-zinc-500">{{ __('No patients found.') }} <a href="{{ route('clinic.patients.create') }}" class="underline" wire:navigate>{{ __('Register a new patient') }}</a></p>
                        @endif
                        <flux:error name="form.patient_id" />
                    @endif
                </div>

                <div class="grid gap-4 sm:grid-cols-4">
                    <flux:select wire:model.live="form.dentist_id" :label="__('Dentist')">
                        @foreach ($this->dentists as $dentist)
                            <flux:select.option value="{{ $dentist->id }}">{{ $dentist->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="form.service_id" :label="__('Service')">
                        <flux:select.option value="">{{ __('Not specified') }}</flux:select.option>
                        @foreach ($this->services as $service)
                            <flux:select.option value="{{ $service->id }}">{{ $service->translationOrFirst()?->title }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="form.duration" :label="__('Duration')">
                        @foreach ([15, 30, 45, 60, 75, 90, 120, 150, 180] as $minutes)
                            <flux:select.option value="{{ $minutes }}">{{ trans_choice(':count minute|:count minutes', $minutes) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input type="date" wire:model.live="form.date" :label="__('Date')" />
                </div>

                <div>
                    <p class="mb-2 text-sm font-medium">{{ __('Free times') }}</p>
                    @if ($this->freeSlots === [])
                        <p class="text-sm text-zinc-500">{{ __('No free times on this day for this dentist. Choose another day, or enter a time below.') }}</p>
                    @else
                        <div class="flex flex-wrap gap-2" role="group" aria-label="{{ __('Free times') }}">
                            @foreach ($this->freeSlots as $slot)
                                <flux:button size="xs" :variant="$form['time'] === $slot ? 'primary' : 'outline'" wire:click="$set('form.time', '{{ $slot }}')" dir="ltr">{{ $slot }}</flux:button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="grid gap-4 sm:grid-cols-[10rem_1fr] sm:items-end">
                    <flux:input type="time" wire:model="form.time" :label="__('Time')" />
                    <flux:checkbox wire:model="form.outside_hours" :label="__('Book outside working hours')" />
                </div>
                <flux:error name="form.time" />
                <flux:textarea wire:model="form.notes" :label="__('Notes for reception')" rows="2" :description="__('Administrative notes only. Clinical information belongs in clinical notes.')" />

                <div class="flex gap-2">
                    <flux:button type="submit" variant="primary">{{ $movingId ? __('Move appointment') : __('Book appointment') }}</flux:button>
                    <flux:button wire:click="$set('booking', false)">{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    @if ($this->dentists->isEmpty())
        <x-ui.empty-state :title="__('No dentists yet')" :description="__('An administrator adds dentists under “Dentists and staff”.')" />
    @else
        <div class="grid gap-4" style="grid-template-columns: repeat({{ max(1, min(4, $this->dentists->count())) }}, minmax(16rem, 1fr));">
            @foreach ($this->dentists as $dentist)
                @php $own = $this->appointments->where('dentist_id', $dentist->id); @endphp
                <div wire:key="col-{{ $dentist->id }}" class="min-w-0">
                    <div class="mb-2 flex items-center justify-between">
                        <flux:heading>{{ $dentist->name }}</flux:heading>
                        @if ($this->canManage())
                            <flux:button size="xs" variant="ghost" icon="plus" wire:click="openBooking({{ $dentist->id }})" :aria-label="__('New appointment with :name', ['name' => $dentist->name])" />
                        @endif
                    </div>
                    @if ($own->isEmpty())
                        <p class="rounded-md border border-dashed border-zinc-300 p-3 text-sm text-zinc-500 dark:border-zinc-700">{{ __('No appointments') }}</p>
                    @else
                        <ol class="space-y-2">
                            @foreach ($own as $appointment)
                                @php $local = \App\Support\ClinicTime::local($appointment->starts_at); $localEnd = \App\Support\ClinicTime::local($appointment->ends_at); @endphp
                                <li wire:key="appt-{{ $appointment->id }}" @class(['rounded-md border p-3 text-sm', 'border-zinc-200 dark:border-zinc-700', 'opacity-60' => in_array($appointment->status->value, ['cancelled', 'no_show'], true)])>
                                    <div class="flex items-start justify-between gap-2">
                                        <span class="font-semibold" dir="ltr">{{ $local->format('H:i') }}–{{ $localEnd->format('H:i') }}</span>
                                        <flux:badge size="sm" :color="$statusColors[$appointment->status->value]">{{ $appointment->status->label() }}</flux:badge>
                                    </div>
                                    @can('view', $appointment->patient)
                                        <a href="{{ route('clinic.patients.show', $appointment->patient) }}" wire:navigate class="mt-1 block font-medium underline">{{ $appointment->patient->full_name }}</a>
                                    @else
                                        <span class="mt-1 block font-medium">{{ $appointment->patient->full_name }}</span>
                                    @endcan
                                    <span class="block text-xs text-zinc-500" dir="ltr">{{ $appointment->patient->patient_number }} · {{ $appointment->patient->phone }}</span>
                                    @if ($appointment->service) <span class="block text-xs">{{ $appointment->service->translationOrFirst()?->title }}</span> @endif
                                    @if ($appointment->notes) <p class="mt-1 whitespace-pre-line text-xs text-zinc-600 dark:text-zinc-400" dir="auto">{{ $appointment->notes }}</p> @endif
                                    @if ($appointment->cancellation_reason) <p class="mt-1 text-xs">{{ __('Reason') }}: {{ $appointment->cancellation_reason }}</p> @endif

                                    @if ($this->canManage() && ! $appointment->status->isFinal())
                                        <div class="mt-2 flex flex-wrap gap-1">
                                            @foreach ([[\App\Enums\AppointmentStatus::Confirmed, __('Confirm')], [\App\Enums\AppointmentStatus::CheckedIn, __('Check in')], [\App\Enums\AppointmentStatus::Completed, __('Complete')], [\App\Enums\AppointmentStatus::NoShow, __('No show')]] as [$next, $label])
                                                @if ($appointment->status->canTransitionTo($next))
                                                    <flux:button size="xs" wire:click="setStatus({{ $appointment->id }}, '{{ $next->value }}')">{{ $label }}</flux:button>
                                                @endif
                                            @endforeach
                                            <flux:button size="xs" variant="ghost" wire:click="openReschedule({{ $appointment->id }})">{{ __('Move') }}</flux:button>
                                            <flux:button size="xs" variant="ghost" wire:click="startCancel({{ $appointment->id }})">{{ __('Cancel') }}</flux:button>
                                        </div>
                                    @endif
                                    @if ($cancellingId === $appointment->id)
                                        <form wire:submit="confirmCancel" class="mt-2 space-y-2">
                                            <flux:input size="sm" wire:model="cancelReason" :label="__('Reason for cancelling')" />
                                            <div class="flex gap-1">
                                                <flux:button size="xs" type="submit" variant="danger">{{ __('Cancel appointment') }}</flux:button>
                                                <flux:button size="xs" wire:click="$set('cancellingId', null)">{{ __('Keep it') }}</flux:button>
                                            </div>
                                        </form>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</section>
