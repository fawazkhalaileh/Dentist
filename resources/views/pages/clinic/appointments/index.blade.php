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

new #[Title('Appointments')] class extends Component
{
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

    /** Appointment whose details panel is open. */
    #[Locked]
    public ?int $selectedId = null;

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

        return Patient::query()->search($term)->orderBy('last_name')->limit(8)->get();
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

    /**
     * The day as a time grid: visible range (minutes since local midnight) and,
     * per dentist, the time they are not working (outside hours, breaks) and closures.
     *
     * @return array{start: int, end: int, columns: array<int, array{off: list<array{0: int, 1: int}>, closures: list<array{0: int, 1: int, 2: ?string}>}>}
     */
    #[Computed]
    public function grid(): array
    {
        $start = 8 * 60;
        $end = 18 * 60;
        $columns = [];
        $dayStart = ClinicTime::toUtc($this->date, '00:00');
        $dayEnd = $dayStart->addDay();
        $toMinutes = function ($instant) use ($dayStart, $dayEnd): int {
            if ($instant <= $dayStart) {
                return 0;
            }
            if ($instant >= $dayEnd) {
                return 1440;
            }
            $local = ClinicTime::local($instant);

            return $local->hour * 60 + $local->minute;
        };

        foreach ($this->dentists as $dentist) {
            $working = Availability::workingIntervals($dentist, $this->date);
            foreach ($working as [$from, $to]) {
                $start = min($start, (int) floor($from / 60) * 60);
                $end = max($end, (int) ceil($to / 60) * 60);
            }
            $closures = [];
            foreach (Availability::overlapping(\App\Models\BlockedPeriod::where(fn ($q) => $q->whereNull('dentist_id')->orWhere('dentist_id', $dentist->id)), $dayStart, $dayEnd)->get() as $period) {
                $closures[] = [$toMinutes($period->starts_at), $toMinutes($period->ends_at), $period->reason];
            }
            $columns[$dentist->id] = ['working' => $working, 'closures' => $closures];
        }

        foreach ($this->appointments as $appointment) {
            $start = min($start, (int) floor($toMinutes($appointment->starts_at) / 60) * 60);
            $end = max($end, (int) ceil($toMinutes($appointment->ends_at) / 60) * 60);
        }

        // Turn working ranges into the "not working" ranges inside the visible window.
        foreach ($columns as $id => $column) {
            $off = [];
            $cursor = $start;
            foreach ($column['working'] as [$from, $to]) {
                if ($from > $cursor) {
                    $off[] = [$cursor, $from];
                }
                $cursor = max($cursor, $to);
            }
            if ($cursor < $end) {
                $off[] = [$cursor, $end];
            }
            $columns[$id] = ['off' => $off, 'closures' => $column['closures']];
        }

        return ['start' => $start, 'end' => $end, 'columns' => $columns];
    }

    #[Computed]
    public function selected(): ?Appointment
    {
        return $this->selectedId ? $this->appointments->firstWhere('id', $this->selectedId) : null;
    }

    public function selectAppointment(int $id): void
    {
        $this->selectedId = $this->visibleAppointment($id)->id;
        $this->cancellingId = null;
    }

    public function closeDetails(): void
    {
        $this->selectedId = null;
        $this->cancellingId = null;
    }

    /**
     * Click on an empty slot: open the booking form for that dentist and time.
     */
    public function bookAt(int $dentistId, string $time): void
    {
        abort_unless(preg_match('/^\d{2}:\d{2}$/', $time) === 1, 422);
        $this->openBooking($dentistId);
        $this->form['time'] = $time;
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
        $this->selectedId = null;
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

    /**
     * Close the cancel form. (The id is locked, so it cannot be cleared with $set from the browser.)
     */
    public function keepAppointment(): void
    {
        $this->cancellingId = null;
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
        $blockStyles = [
            'scheduled' => 'border-zinc-400 bg-white dark:bg-zinc-800',
            'confirmed' => 'border-sky-500 bg-sky-50 dark:bg-sky-950',
            'checked_in' => 'border-amber-500 bg-amber-50 dark:bg-amber-950',
            'completed' => 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950',
        ];
        $day = \Carbon\CarbonImmutable::createFromFormat('Y-m-d', $date);
        $isToday = $date === \App\Support\ClinicTime::today();
        $grid = $this->grid;
        $ppm = 1.6; // pixels per minute: 15 minutes = 24px
        $height = ($grid['end'] - $grid['start']) * $ppm;
        $now = \App\Support\ClinicTime::now();
        $nowMinutes = $now->hour * 60 + $now->minute;
        $visible = $this->appointments->whereNotIn('status', [\App\Enums\AppointmentStatus::Cancelled, \App\Enums\AppointmentStatus::NoShow]);
        $offList = $this->appointments->whereIn('status', [\App\Enums\AppointmentStatus::Cancelled, \App\Enums\AppointmentStatus::NoShow]);
        $minutesOf = fn ($instant) => \App\Support\ClinicTime::local($instant)->hour * 60 + \App\Support\ClinicTime::local($instant)->minute;
        $fmt = fn (int $m) => sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
    @endphp

    {{-- Toolbar --}}
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <h1 class="me-2 text-2xl font-semibold">{{ __('Calendar') }}</h1>
            <flux:button size="sm" icon="chevron-left" wire:click="shiftDay(-1)" :aria-label="__('Previous day')" />
            <flux:button size="sm" wire:click="today" :variant="$isToday ? 'filled' : 'outline'">{{ __('Today') }}</flux:button>
            <flux:button size="sm" icon="chevron-right" wire:click="shiftDay(1)" :aria-label="__('Next day')" />
            <span class="ms-1 font-medium">{{ $day->translatedFormat('l j F Y') }}</span>
            <input type="date" wire:model.live="date" aria-label="{{ __('Go to date') }}" class="h-8 rounded-lg border border-zinc-200 bg-white px-2 text-sm dark:border-zinc-600 dark:bg-zinc-800" />
        </div>
        @if ($this->canManage())
            <flux:button variant="primary" icon="plus" wire:click="openBooking">{{ __('New appointment') }}</flux:button>
        @endif
    </div>

    @if ($this->canManage() && $this->dentists->isNotEmpty())
        <p class="mb-3 text-xs text-zinc-500">{{ __('Click an empty time to book it. Grey areas are outside working hours. Times are in :zone.', ['zone' => \App\Support\ClinicTime::zone()]) }}</p>
    @endif

    @if ($this->dentists->isEmpty())
        <x-ui.empty-state :title="__('No dentists yet')" :description="__('An administrator adds dentists under “Dentists and staff”, then sets their working hours.')" />
    @else
        <div class="flex gap-4">
            {{-- Day grid --}}
            <div class="min-w-0 flex-1 overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                <div class="flex" style="min-width: {{ 4 + $this->dentists->count() * 13 }}rem">
                    {{-- Time axis --}}
                    <div class="w-14 shrink-0 border-e border-zinc-200 dark:border-zinc-700">
                        <div class="h-10 border-b border-zinc-200 dark:border-zinc-700"></div>
                        <div class="relative" style="height: {{ $height }}px" dir="ltr">
                            @for ($m = $grid['start']; $m < $grid['end']; $m += 60)
                                <span class="absolute end-2 -translate-y-1/2 text-xs text-zinc-500" style="top: {{ ($m - $grid['start']) * $ppm }}px">@if ($m > $grid['start']){{ $fmt($m) }}@endif</span>
                            @endfor
                        </div>
                    </div>

                    @foreach ($this->dentists as $dentist)
                        @php $column = $grid['columns'][$dentist->id] ?? ['off' => [], 'closures' => []]; @endphp
                        <div class="min-w-52 flex-1 border-e border-zinc-200 last:border-e-0 dark:border-zinc-700" wire:key="col-{{ $dentist->id }}">
                            <div class="flex h-10 items-center justify-between border-b border-zinc-200 px-3 dark:border-zinc-700">
                                <span class="truncate text-sm font-semibold">{{ $dentist->name }}</span>
                                <span class="text-xs text-zinc-500">{{ $visible->where('dentist_id', $dentist->id)->count() }}</span>
                            </div>
                            <div class="relative" style="height: {{ $height }}px">
                                {{-- Hour and quarter lines --}}
                                @for ($m = $grid['start']; $m < $grid['end']; $m += 15)
                                    <div @class(['absolute inset-x-0 border-t', 'border-zinc-200 dark:border-zinc-700' => $m % 60 === 0, 'border-zinc-100 dark:border-zinc-800' => $m % 60 !== 0]) style="top: {{ ($m - $grid['start']) * $ppm }}px"></div>
                                @endfor

                                {{-- Not working --}}
                                @foreach ($column['off'] as [$from, $to])
                                    <div class="absolute inset-x-0 bg-zinc-100/80 dark:bg-zinc-800/80" style="top: {{ ($from - $grid['start']) * $ppm }}px; height: {{ ($to - $from) * $ppm }}px" aria-hidden="true"></div>
                                @endforeach
                                @foreach ($column['closures'] as [$from, $to, $reason])
                                    @php $from = max($from, $grid['start']); $to = min($to, $grid['end']); @endphp
                                    @if ($to > $from)
                                        <div class="absolute inset-x-0 flex items-start justify-center bg-[repeating-linear-gradient(135deg,theme(colors.zinc.200)_0_6px,transparent_6px_12px)] p-1 text-xs font-medium text-zinc-600 dark:bg-[repeating-linear-gradient(135deg,theme(colors.zinc.700)_0_6px,transparent_6px_12px)] dark:text-zinc-300" style="top: {{ ($from - $grid['start']) * $ppm }}px; height: {{ ($to - $from) * $ppm }}px">
                                            {{ $reason ?: __('Closed') }}
                                        </div>
                                    @endif
                                @endforeach

                                {{-- Click an empty quarter-hour to book --}}
                                @if ($this->canManage())
                                    @for ($m = $grid['start']; $m < $grid['end']; $m += 15)
                                        <button type="button" wire:click="bookAt({{ $dentist->id }}, '{{ $fmt($m) }}')"
                                                class="group absolute inset-x-0 z-0 hover:bg-brand-soft focus:bg-brand-soft focus:outline-none"
                                                style="top: {{ ($m - $grid['start']) * $ppm }}px; height: {{ 15 * $ppm }}px"
                                                aria-label="{{ __('Book :time with :name', ['time' => $fmt($m), 'name' => $dentist->name]) }}">
                                            <span class="hidden ps-2 text-[10px] font-medium text-brand group-hover:inline group-focus:inline" dir="ltr">+ {{ $fmt($m) }}</span>
                                        </button>
                                    @endfor
                                @endif

                                {{-- Appointments --}}
                                @foreach ($visible->where('dentist_id', $dentist->id) as $appointment)
                                    @php
                                        $top = ($minutesOf($appointment->starts_at) - $grid['start']) * $ppm;
                                        $minutes = max(15, (int) $appointment->starts_at->diffInMinutes($appointment->ends_at));
                                        $local = \App\Support\ClinicTime::local($appointment->starts_at);
                                        $localEnd = \App\Support\ClinicTime::local($appointment->ends_at);
                                    @endphp
                                    <button type="button" wire:click="selectAppointment({{ $appointment->id }})" wire:key="appt-{{ $appointment->id }}"
                                            @class(['absolute inset-x-1 z-10 overflow-hidden rounded-md border-s-4 border px-2 py-1 text-start text-xs shadow-sm hover:shadow-md focus:outline-2 focus:outline-brand',
                                                    $blockStyles[$appointment->status->value] ?? $blockStyles['scheduled'],
                                                    'ring-2 ring-brand' => $selectedId === $appointment->id])
                                            style="top: {{ $top }}px; height: {{ $minutes * $ppm - 2 }}px">
                                        <span class="block truncate font-semibold">{{ $appointment->patient->full_name }}</span>
                                        <span class="block truncate text-zinc-600 dark:text-zinc-300"><span dir="ltr">{{ $local->format('H:i') }}–{{ $localEnd->format('H:i') }}</span>@if ($appointment->service) · {{ $appointment->service->translationOrFirst()?->title }}@endif</span>
                                        @if ($minutes >= 45) <span class="mt-0.5 block truncate text-zinc-500">{{ $appointment->status->label() }}</span> @endif
                                    </button>
                                @endforeach

                                {{-- Now --}}
                                @if ($isToday && $nowMinutes >= $grid['start'] && $nowMinutes < $grid['end'])
                                    <div class="pointer-events-none absolute inset-x-0 z-20 border-t-2 border-red-500" style="top: {{ ($nowMinutes - $grid['start']) * $ppm }}px" aria-hidden="true">
                                        <span class="absolute -start-1 -top-1.5 size-2.5 rounded-full bg-red-500"></span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Details of the selected appointment --}}
            @if ($appointment = $this->selected)
                @php $local = \App\Support\ClinicTime::local($appointment->starts_at); $localEnd = \App\Support\ClinicTime::local($appointment->ends_at); @endphp
                <aside class="hidden w-80 shrink-0 lg:block" aria-label="{{ __('Appointment details') }}">
                    <div class="sticky top-4 space-y-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="text-xs text-zinc-500">{{ $appointment->dentist?->name ?? $this->dentists->firstWhere('id', $appointment->dentist_id)?->name }}</p>
                                <p class="text-lg font-semibold" dir="ltr">{{ $local->format('H:i') }}–{{ $localEnd->format('H:i') }}</p>
                            </div>
                            <flux:button size="xs" variant="ghost" icon="x-mark" wire:click="closeDetails" :aria-label="__('Close')" />
                        </div>
                        <div>
                            @can('view', $appointment->patient)
                                <a href="{{ route('clinic.patients.show', $appointment->patient) }}" wire:navigate class="font-semibold hover:underline">{{ $appointment->patient->full_name }}</a>
                            @else
                                <span class="font-semibold">{{ $appointment->patient->full_name }}</span>
                            @endcan
                            <p class="text-sm text-zinc-500" dir="ltr">{{ $appointment->patient->patient_number }} · <a href="tel:{{ preg_replace('/[^0-9+]/', '', (string) $appointment->patient->phone) }}" class="underline">{{ $appointment->patient->phone }}</a></p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 text-sm">
                            <flux:badge size="sm" :color="$statusColors[$appointment->status->value]">{{ $appointment->status->label() }}</flux:badge>
                            @if ($appointment->service) <span>{{ $appointment->service->translationOrFirst()?->title }}</span> @endif
                        </div>
                        @if ($appointment->notes) <p class="whitespace-pre-line rounded-md bg-zinc-50 p-2 text-sm dark:bg-zinc-800" dir="auto">{{ $appointment->notes }}</p> @endif

                        @if ($this->canManage() && ! $appointment->status->isFinal())
                            <div class="grid grid-cols-2 gap-2">
                                @foreach ([[\App\Enums\AppointmentStatus::Confirmed, __('Confirm')], [\App\Enums\AppointmentStatus::CheckedIn, __('Check in')], [\App\Enums\AppointmentStatus::Completed, __('Complete')], [\App\Enums\AppointmentStatus::NoShow, __('No show')]] as [$next, $label])
                                    @if ($appointment->status->canTransitionTo($next))
                                        <flux:button size="sm" :variant="$next === \App\Enums\AppointmentStatus::NoShow ? 'ghost' : 'primary'" wire:click="setStatus({{ $appointment->id }}, '{{ $next->value }}')">{{ $label }}</flux:button>
                                    @endif
                                @endforeach
                                <flux:button size="sm" icon="arrows-right-left" wire:click="openReschedule({{ $appointment->id }})">{{ __('Move') }}</flux:button>
                                <flux:button size="sm" variant="ghost" wire:click="startCancel({{ $appointment->id }})">{{ __('Cancel') }}</flux:button>
                            </div>
                            @if ($cancellingId === $appointment->id)
                                <form wire:submit="confirmCancel" class="space-y-2">
                                    <flux:input size="sm" wire:model="cancelReason" :label="__('Reason for cancelling')" />
                                    <div class="flex gap-2">
                                        <flux:button size="sm" type="submit" variant="danger">{{ __('Cancel appointment') }}</flux:button>
                                        <flux:button size="sm" wire:click="keepAppointment">{{ __('Keep it') }}</flux:button>
                                    </div>
                                </form>
                            @endif
                        @endif
                    </div>
                </aside>
            @endif
        </div>

        {{-- On small screens, details appear below the grid --}}
        @if ($appointment = $this->selected)
            <div class="mt-4 rounded-xl border border-zinc-200 p-4 lg:hidden dark:border-zinc-700">
                <div class="flex items-center justify-between">
                    <p class="font-semibold">{{ $appointment->patient->full_name }} · <span dir="ltr">{{ \App\Support\ClinicTime::local($appointment->starts_at)->format('H:i') }}</span></p>
                    <flux:button size="xs" variant="ghost" icon="x-mark" wire:click="closeDetails" :aria-label="__('Close')" />
                </div>
                @if ($this->canManage() && ! $appointment->status->isFinal())
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ([[\App\Enums\AppointmentStatus::Confirmed, __('Confirm')], [\App\Enums\AppointmentStatus::CheckedIn, __('Check in')], [\App\Enums\AppointmentStatus::Completed, __('Complete')], [\App\Enums\AppointmentStatus::NoShow, __('No show')]] as [$next, $label])
                            @if ($appointment->status->canTransitionTo($next))
                                <flux:button size="sm" wire:click="setStatus({{ $appointment->id }}, '{{ $next->value }}')">{{ $label }}</flux:button>
                            @endif
                        @endforeach
                        <flux:button size="sm" wire:click="openReschedule({{ $appointment->id }})">{{ __('Move') }}</flux:button>
                        <flux:button size="sm" variant="ghost" wire:click="startCancel({{ $appointment->id }})">{{ __('Cancel') }}</flux:button>
                    </div>
                    @if ($cancellingId === $appointment->id)
                        <form wire:submit="confirmCancel" class="mt-3 flex flex-wrap items-end gap-2">
                            <flux:input size="sm" wire:model="cancelReason" :label="__('Reason for cancelling')" />
                            <flux:button size="sm" type="submit" variant="danger">{{ __('Cancel appointment') }}</flux:button>
                            <flux:button size="sm" wire:click="keepAppointment">{{ __('Keep it') }}</flux:button>
                        </form>
                    @endif
                @endif
            </div>
        @endif

        @if ($offList->isNotEmpty())
            <details class="mt-4 text-sm">
                <summary class="cursor-pointer text-zinc-500">{{ trans_choice(':count cancelled or missed appointment|:count cancelled or missed appointments', $offList->count()) }}</summary>
                <ul class="mt-2 space-y-1">
                    @foreach ($offList as $appointment)
                        <li class="text-zinc-600 dark:text-zinc-400">
                            <span dir="ltr">{{ \App\Support\ClinicTime::local($appointment->starts_at)->format('H:i') }}</span> · {{ $appointment->patient->full_name }} · {{ $appointment->status->label() }}
                            @if ($appointment->cancellation_reason) · {{ __('Reason') }}: {{ $appointment->cancellation_reason }} @endif
                        </li>
                    @endforeach
                </ul>
            </details>
        @endif
    @endif

    {{-- Booking form in a side panel (only for staff who can book) --}}
    @if ($this->canManage())
    <flux:modal wire:model.self="booking" variant="flyout" class="w-full max-w-lg">
        <form wire:submit="book" class="space-y-5">
            <flux:heading size="lg">{{ $movingId ? __('Move appointment') : __('New appointment') }}</flux:heading>

            <div>
                @if ($this->selectedPatient)
                    <p class="mb-1 text-sm font-medium">{{ __('Patient') }}</p>
                    <div class="flex items-center gap-2">
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
                                <li><button type="button" wire:click="pickPatient({{ $result->id }})" class="w-full px-3 py-2 text-start text-sm hover:bg-brand-soft">{{ $result->full_name }} <span class="text-zinc-500" dir="ltr">· {{ $result->patient_number }} · {{ $result->phone }}</span></button></li>
                            @endforeach
                        </ul>
                    @elseif (mb_strlen(trim($patientSearch)) >= 2)
                        <p class="mt-1 text-sm text-zinc-500">{{ __('No patients found.') }} <a href="{{ route('clinic.patients.create') }}" class="underline" wire:navigate>{{ __('Register a new patient') }}</a></p>
                    @endif
                    <flux:error name="form.patient_id" />
                @endif
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model.live="form.dentist_id" :label="__('Dentist')">
                    @foreach ($this->dentists as $dentist)
                        <flux:select.option value="{{ $dentist->id }}">{{ $dentist->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input type="date" wire:model.live="form.date" :label="__('Date')" />
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
            </div>

            <div>
                <p class="mb-2 text-sm font-medium">{{ __('Free times') }}</p>
                @if ($this->freeSlots === [])
                    <p class="text-sm text-zinc-500">{{ __('No free times on this day for this dentist. Choose another day, or enter a time below.') }}</p>
                @else
                    <div class="flex max-h-40 flex-wrap gap-2 overflow-y-auto" role="group" aria-label="{{ __('Free times') }}">
                        @foreach ($this->freeSlots as $slot)
                            <flux:button size="xs" :variant="$form['time'] === $slot ? 'primary' : 'outline'" wire:click="$set('form.time', '{{ $slot }}')" dir="ltr">{{ $slot }}</flux:button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="grid gap-4 sm:grid-cols-[9rem_1fr] sm:items-end">
                <flux:input type="time" wire:model="form.time" :label="__('Time')" />
                <flux:checkbox wire:model="form.outside_hours" :label="__('Book outside working hours')" />
            </div>
            <flux:error name="form.time" />
            <flux:textarea wire:model="form.notes" :label="__('Notes for reception')" rows="2" :description="__('Administrative notes only. Clinical information belongs in clinical notes.')" />

            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">{{ $movingId ? __('Move appointment') : __('Book appointment') }}</flux:button>
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
            </div>
        </form>
    </flux:modal>
    @endif
</section>
