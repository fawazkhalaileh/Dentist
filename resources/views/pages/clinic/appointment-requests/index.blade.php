<?php

use App\Actions\Patients\SavePatient;
use App\Actions\Scheduling\BookAppointment;
use App\Enums\AppointmentRequestStatus;
use App\Enums\Permission;
use App\Enums\Role;
use App\Models\AppointmentRequest;
use App\Models\Patient;
use App\Models\User;
use App\Support\Audit;
use App\Support\Availability;
use App\Support\ClinicTime;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Appointment requests')] class extends Component {
    #[Url]
    public bool $showHandled = false;

    /** Request being booked. */
    #[Locked]
    public ?int $bookingId = null;

    /** Existing patient id, or 0 to register a new patient from the request. */
    public ?int $patientChoice = null;

    /** @var array{first_name: string, last_name: string, dentist_id: ?int, date: string, time: string, duration: int, outside_hours: bool} */
    public array $form = ['first_name' => '', 'last_name' => '', 'dentist_id' => null, 'date' => '', 'time' => '', 'duration' => 30, 'outside_hours' => false];

    public function boot(): void
    {
        Gate::authorize(Permission::AppointmentRequestsManage->value);
    }

    /**
     * @return Collection<int, AppointmentRequest>
     */
    #[Computed]
    public function requests(): Collection
    {
        return AppointmentRequest::with(['service.translations', 'preferredDentist:id,name', 'patient:id,first_name,last_name,patient_number', 'appointment'])
            ->when(! $this->showHandled, fn ($q) => $q->where('status', AppointmentRequestStatus::Pending->value))
            ->latest('id')->limit(100)->get();
    }

    #[Computed]
    public function current(): ?AppointmentRequest
    {
        return $this->bookingId ? AppointmentRequest::find($this->bookingId) : null;
    }

    /**
     * Existing patients with the same phone number (ignoring formatting and the leading 0).
     *
     * @return Collection<int, Patient>
     */
    #[Computed]
    public function matches(): Collection
    {
        $digits = ltrim(preg_replace('/\D+/', '', (string) $this->current?->phone) ?? '', '0');
        if (strlen($digits) < 7) {
            return new Collection;
        }
        $stored = "TRIM(LEADING '0' FROM REGEXP_REPLACE(phone, '[^0-9]', ''))";

        return Patient::whereRaw("({$stored} LIKE ? OR ? LIKE CONCAT('%', {$stored}))", ['%'.$digits, $digits])->limit(5)->get();
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function dentists(): Collection
    {
        return User::havingRole(Role::Dentist)->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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

        return Availability::freeSlots($dentist, $this->form['date'], max(5, (int) $this->form['duration']));
    }

    public function startBooking(int $id): void
    {
        $request = AppointmentRequest::where('status', AppointmentRequestStatus::Pending->value)->findOrFail($id);
        $this->authorize('create', \App\Models\Appointment::class);
        $this->resetValidation();

        [$first, $last] = array_pad(preg_split('/\s+/', trim($request->name), 2) ?: [], 2, '');
        $this->bookingId = $request->id;
        unset($this->current, $this->matches);
        $this->patientChoice = $this->matches->first()?->id ?? 0;
        $this->form = [
            'first_name' => $first,
            'last_name' => $last,
            'dentist_id' => $request->preferred_dentist_id ?? $this->dentists->first()?->id,
            'date' => $request->preferred_date && $request->preferred_date->format('Y-m-d') >= ClinicTime::today() ? $request->preferred_date->format('Y-m-d') : ClinicTime::today(),
            'time' => '',
            'duration' => $request->service?->duration_minutes ?? 30,
            'outside_hours' => false,
        ];
    }

    public function book(BookAppointment $book, SavePatient $savePatient): void
    {
        $request = $this->current;
        abort_unless($request !== null && $request->status === AppointmentRequestStatus::Pending, 422);
        $this->authorize('create', \App\Models\Appointment::class);

        $this->validate([
            'patientChoice' => ['required', 'integer', Rule::in([0, ...$this->matches->pluck('id')->all()])],
            'form.first_name' => ['exclude_unless:patientChoice,0', 'required', 'string', 'max:100'],
            'form.last_name' => ['exclude_unless:patientChoice,0', 'required', 'string', 'max:100'],
            'form.dentist_id' => ['required', 'integer', Rule::in($this->dentists->pluck('id')->all())],
            'form.date' => ['required', 'date_format:Y-m-d'],
            'form.time' => ['required', 'date_format:H:i'],
            'form.duration' => ['required', 'integer', 'min:5', 'max:480'],
            'form.outside_hours' => ['boolean'],
        ], ['form.time.required' => __('Choose a time.')]);

        $user = Auth::user();
        abort_unless($user instanceof User, 403);
        $starts = ClinicTime::toUtc($this->form['date'], $this->form['time']);

        try {
            DB::transaction(function () use ($request, $book, $savePatient, $user, $starts) {
                $patient = $this->patientChoice
                    ? Patient::findOrFail($this->patientChoice)
                    : $savePatient->handle([
                        'first_name' => trim($this->form['first_name']),
                        'last_name' => trim($this->form['last_name']),
                        'phone' => $request->phone,
                        'email' => $request->email,
                        'preferred_locale' => $request->locale,
                    ], [], $user);

                $appointment = $book->handle($patient, User::findOrFail($this->form['dentist_id']), $starts, $starts->addMinutes((int) $this->form['duration']),
                    ['service_id' => $request->service_id, 'notes' => null, 'source' => 'public_request'], $user, (bool) $this->form['outside_hours']);

                $request->update(['patient_id' => $patient->id]);
                $request->forceFill(['status' => AppointmentRequestStatus::Approved, 'handled_by' => $user->id, 'handled_at' => now(), 'appointment_id' => $appointment->id])->save();
                Audit::record('appointment_request.approved', $request, ['appointment_id' => $appointment->id]);
            });
        } catch (ValidationException $e) {
            $this->addError('form.time', collect($e->errors())->flatten()->first());

            return;
        }

        $this->bookingId = null;
        unset($this->requests);
        Flux::toast(variant: 'success', text: __('Appointment booked. Remember to call the patient to confirm.'));
    }

    public function closeBooking(): void
    {
        $this->bookingId = null;
    }

    public function decline(int $id): void
    {
        $request = AppointmentRequest::where('status', AppointmentRequestStatus::Pending->value)->findOrFail($id);
        $request->forceFill(['status' => AppointmentRequestStatus::Declined, 'handled_by' => Auth::id(), 'handled_at' => now()])->save();
        Audit::record('appointment_request.declined', $request);

        if ($this->bookingId === $id) {
            $this->bookingId = null;
        }
        unset($this->requests);
        Flux::toast(variant: 'success', text: __('Request marked as declined.'));
    }
}; ?>

<section class="w-full max-w-5xl">
    <x-ui.page-header :title="__('Appointment requests')" :description="__('Requests sent from the website. Call the person, agree a time, then book it here.')" />

    <flux:checkbox wire:model.live="showHandled" :label="__('Show handled requests')" class="mb-4" />

    @if ($this->requests->isEmpty())
        <x-ui.empty-state :title="__('No requests waiting')" :description="$showHandled ? null : __('Requests from the website’s “Request an appointment” page and from the patient portal appear here.')" />
    @else
        <ul class="space-y-3">
            @foreach ($this->requests as $request)
                <li wire:key="request-{{ $request->id }}">
                    <flux:card class="space-y-3">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <p class="font-medium">{{ $request->name }}</p>
                                <p class="text-sm text-zinc-500">
                                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $request->phone) }}" class="underline" dir="ltr">{{ $request->phone }}</a>
                                    @if ($request->email) · <span dir="ltr">{{ $request->email }}</span> @endif
                                    · {{ $request->created_at?->diffForHumans() }} · <span class="uppercase">{{ $request->locale }}</span>
                                </p>
                            </div>
                            <flux:badge size="sm" :color="match ($request->status->value) { 'pending' => 'amber', 'approved' => 'green', default => 'zinc' }">{{ $request->status->label() }}</flux:badge>
                        </div>
                        <dl class="grid gap-x-6 gap-y-1 text-sm sm:grid-cols-2">
                            <div><dt class="inline text-zinc-500">{{ __('Treatment') }}:</dt> <dd class="inline">{{ $request->service?->translationOrFirst()?->title ?? __('Not specified') }}</dd></div>
                            <div><dt class="inline text-zinc-500">{{ __('Preferred dentist') }}:</dt> <dd class="inline">{{ $request->preferredDentist?->name ?? __('No preference') }}</dd></div>
                            <div><dt class="inline text-zinc-500">{{ __('Preferred date') }}:</dt> <dd class="inline">{{ $request->preferred_date?->format('Y-m-d') ?? __('Any') }}</dd></div>
                            <div><dt class="inline text-zinc-500">{{ __('Preferred time') }}:</dt> <dd class="inline">{{ \App\Models\AppointmentRequest::preferredTimeLabel($request->preferred_time) }}</dd></div>
                        </dl>
                        @if ($request->message) <p class="whitespace-pre-line text-sm" dir="auto">{{ $request->message }}</p> @endif
                        @if ($request->appointment)
                            <p class="text-sm">{{ __('Booked for :time with :patient.', ['time' => \App\Support\ClinicTime::local($request->appointment->starts_at)->format('Y-m-d H:i'), 'patient' => $request->patient?->full_name]) }}</p>
                        @endif

                        @if ($request->status === \App\Enums\AppointmentRequestStatus::Pending)
                            @if ($bookingId === $request->id)
                                <form wire:submit="book" class="space-y-4 rounded-md bg-zinc-50 p-4 dark:bg-zinc-900">
                                    <flux:radio.group wire:model.live="patientChoice" :label="__('Patient')">
                                        @foreach ($this->matches as $match)
                                            <flux:radio value="{{ $match->id }}" :label="$match->full_name.' · '.$match->patient_number.' · '.$match->phone" />
                                        @endforeach
                                        <flux:radio value="0" :label="__('New patient')" />
                                    </flux:radio.group>
                                    @if ((int) $patientChoice === 0)
                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <flux:input wire:model="form.first_name" :label="__('First name')" />
                                            <flux:input wire:model="form.last_name" :label="__('Last name')" />
                                        </div>
                                    @endif
                                    <div class="grid gap-4 sm:grid-cols-3">
                                        <flux:select wire:model.live="form.dentist_id" :label="__('Dentist')">
                                            @foreach ($this->dentists as $dentist)
                                                <flux:select.option value="{{ $dentist->id }}">{{ $dentist->name }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <flux:input type="date" wire:model.live="form.date" :label="__('Date')" />
                                        <flux:select wire:model.live="form.duration" :label="__('Duration')">
                                            @foreach ([15, 30, 45, 60, 90, 120] as $minutes)
                                                <flux:select.option value="{{ $minutes }}">{{ trans_choice(':count minute|:count minutes', $minutes) }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    </div>
                                    @if ($this->freeSlots === [])
                                        <p class="text-sm text-zinc-500">{{ __('No free times on this day. Choose another day, or enter a time.') }}</p>
                                    @else
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($this->freeSlots as $slot)
                                                <flux:button size="xs" :variant="$form['time'] === $slot ? 'primary' : 'outline'" wire:click="$set('form.time', '{{ $slot }}')" dir="ltr">{{ $slot }}</flux:button>
                                            @endforeach
                                        </div>
                                    @endif
                                    <div class="grid gap-4 sm:grid-cols-[10rem_1fr] sm:items-end">
                                        <flux:input type="time" wire:model="form.time" :label="__('Time')" />
                                        <flux:checkbox wire:model="form.outside_hours" :label="__('Book outside working hours')" />
                                    </div>
                                    <flux:error name="form.time" />
                                    <div class="flex gap-2">
                                        <flux:button type="submit" variant="primary">{{ __('Book appointment') }}</flux:button>
                                        <flux:button wire:click="closeBooking">{{ __('Cancel') }}</flux:button>
                                    </div>
                                </form>
                            @else
                                <div class="flex gap-2">
                                    <flux:button size="sm" variant="primary" wire:click="startBooking({{ $request->id }})">{{ __('Book appointment') }}</flux:button>
                                    <flux:button size="sm" variant="ghost" wire:click="decline({{ $request->id }})" wire:confirm="{{ __('Mark this request as declined?') }}">{{ __('Decline') }}</flux:button>
                                </div>
                            @endif
                        @endif
                    </flux:card>
                </li>
            @endforeach
        </ul>
    @endif
</section>
