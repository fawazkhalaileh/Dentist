<?php

use App\Actions\Scheduling\ChangeAppointmentStatus;
use App\Enums\AppointmentRequestStatus;
use App\Enums\AppointmentStatus;
use App\Enums\Permission;
use App\Enums\Role;
use App\Enums\TreatmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentRequest;
use App\Models\ContactMessage;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\TreatmentItem;
use App\Models\User;
use App\Support\ClinicDetails;
use App\Support\ClinicTime;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The clinic home page. What it shows follows the user's permissions: front-desk
 * staff see the whole clinic's day, dentists their own day and patients' planned
 * work, administrators a setup checklist until the clinic is ready.
 */
new #[Title('Home')] class extends Component {
    public string $patientSearch = '';

    public function boot(): void
    {
        $this->authorize('viewAny', Appointment::class);
    }

    private function user(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    public function seesWholeClinic(): bool
    {
        return $this->user()->can(Permission::AppointmentsViewAll->value);
    }

    public function canManage(): bool
    {
        return $this->user()->can(Permission::AppointmentsManage->value);
    }

    public function greeting(): string
    {
        $hour = ClinicTime::now()->hour;

        return match (true) {
            $hour < 12 => __('Good morning, :name', ['name' => $this->firstName()]),
            $hour < 17 => __('Good afternoon, :name', ['name' => $this->firstName()]),
            default => __('Good evening, :name', ['name' => $this->firstName()]),
        };
    }

    private function firstName(): string
    {
        return explode(' ', trim(preg_replace('/^(Dr\.?|Dr)\s+/i', '', $this->user()->name) ?? ''))[0] ?: $this->user()->name;
    }

    /**
     * Today's appointments in the clinic's time zone: the whole clinic, or your own.
     *
     * @return Collection<int, Appointment>
     */
    #[Computed]
    public function today(): Collection
    {
        $start = ClinicTime::toUtc(ClinicTime::today(), '00:00');

        return Appointment::with(['patient:id,first_name,last_name,patient_number', 'dentist:id,name', 'service.translations'])
            ->when(! $this->seesWholeClinic(), fn ($q) => $q->where('dentist_id', $this->user()->id))
            ->where('starts_at', '>=', $start)->where('starts_at', '<', $start->addDay())
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * Appointment counts for the next seven days (clinic time), for the week strip.
     *
     * @return list<array{date: string, count: int}>
     */
    #[Computed]
    public function week(): array
    {
        $first = ClinicTime::toUtc(ClinicTime::today(), '00:00');
        $appointments = Appointment::query()
            ->when(! $this->seesWholeClinic(), fn ($q) => $q->where('dentist_id', $this->user()->id))
            ->whereNotIn('status', [AppointmentStatus::Cancelled->value])
            ->where('starts_at', '>=', $first)->where('starts_at', '<', $first->addDays(7))
            ->pluck('starts_at');

        $counts = [];
        foreach ($appointments as $startsAt) {
            $day = ClinicTime::local($startsAt)->format('Y-m-d');
            $counts[$day] = ($counts[$day] ?? 0) + 1;
        }

        $days = [];
        foreach (range(0, 6) as $offset) {
            $date = ClinicTime::now()->addDays($offset)->format('Y-m-d');
            $days[] = ['date' => $date, 'count' => $counts[$date] ?? 0];
        }

        return $days;
    }

    /**
     * @return array{requests: ?int, messages: ?int, patients_new: ?int, planned: ?int}
     */
    #[Computed]
    public function counts(): array
    {
        $user = $this->user();

        return [
            'requests' => $user->can(Permission::AppointmentRequestsManage->value) ? AppointmentRequest::where('status', AppointmentRequestStatus::Pending->value)->count() : null,
            'messages' => $user->can(Permission::ContentManage->value) ? ContactMessage::whereNull('handled_at')->count() : null,
            'patients_new' => $user->can(Permission::PatientsViewAll->value) ? Patient::where('created_at', '>=', now()->subDays(30))->count() : null,
            'planned' => $user->hasRoleEnum(Role::Dentist)
                ? TreatmentItem::where('dentist_id', $user->id)->whereIn('status', [TreatmentStatus::Planned->value, TreatmentStatus::InProgress->value])->count()
                : null,
        ];
    }

    /**
     * @return Collection<int, AppointmentRequest>
     */
    #[Computed]
    public function requests(): Collection
    {
        return $this->user()->can(Permission::AppointmentRequestsManage->value)
            ? AppointmentRequest::with('service.translations')->where('status', AppointmentRequestStatus::Pending->value)->latest('id')->limit(5)->get()
            : new Collection;
    }

    /**
     * A dentist's own planned and in-progress treatments, soonest first.
     *
     * @return Collection<int, TreatmentItem>
     */
    #[Computed]
    public function myPlanned(): Collection
    {
        if (! $this->user()->hasRoleEnum(Role::Dentist)) {
            return new Collection;
        }

        return TreatmentItem::with(['procedure', 'surfaces', 'patient:id,first_name,last_name'])
            ->where('dentist_id', $this->user()->id)
            ->whereIn('status', [TreatmentStatus::InProgress->value, TreatmentStatus::Planned->value])
            ->orderByRaw("FIELD(status, 'in_progress', 'planned')")
            ->orderByRaw('planned_for IS NULL, planned_for')
            ->limit(6)->get();
    }

    /**
     * Setup steps for a new clinic (administrators). Hidden once all are done.
     *
     * @return list<array{label: string, help: string, done: bool, route: string}>
     */
    #[Computed]
    public function setup(): array
    {
        if (! $this->user()->can(Permission::StaffManage->value)) {
            return [];
        }

        $dentistIds = User::havingRole(Role::Dentist)->where('is_active', true)->pluck('id');

        return [
            ['label' => __('Enter your clinic name'), 'help' => __('Shown on the website and in this system, in English and Arabic.'), 'done' => ClinicDetails::hasName(), 'route' => route('clinic.content.clinic-details')],
            ['label' => __('Add your procedures'), 'help' => __('Press “Add starter procedures” for a ready-made list, then adjust it.'), 'done' => Procedure::exists(), 'route' => route('clinic.procedures.index')],
            ['label' => __('Add your dentists and staff'), 'help' => __('Each person gets a setup link to choose a password.'), 'done' => $dentistIds->isNotEmpty(), 'route' => route('clinic.staff.index')],
            ['label' => __('Set working hours'), 'help' => __('The calendar offers free times only within working hours.'), 'done' => $dentistIds->isNotEmpty() && DB::table('working_hours')->whereIn('dentist_id', $dentistIds)->exists(), 'route' => route('clinic.schedules.index')],
            ['label' => __('Register your first patient'), 'help' => __('Then book their first appointment from the calendar.'), 'done' => Patient::withTrashed()->exists(), 'route' => route('clinic.patients.create')],
        ];
    }

    /**
     * @return Collection<int, Patient>
     */
    #[Computed]
    public function searchResults(): Collection
    {
        if (mb_strlen(trim($this->patientSearch)) < 2) {
            return new Collection;
        }

        $user = $this->user();

        return Patient::query()
            ->unless($user->can(Permission::PatientsViewAll->value), fn ($q) => $q->inCareTeamOf($user))
            ->search($this->patientSearch)
            ->orderBy('last_name')->limit(6)->get();
    }

    public function setStatus(int $id, string $status, ChangeAppointmentStatus $change): void
    {
        $appointment = Appointment::findOrFail($id);
        $this->authorize('update', $appointment);
        $to = AppointmentStatus::from($status);
        abort_if($to === AppointmentStatus::Cancelled, 422);

        $change->handle($appointment, $to, $this->user());
        unset($this->today);
        Flux::toast(variant: 'success', text: __(':name: :status', ['name' => $appointment->patient->full_name, 'status' => $to->label()]));
    }
}; ?>

<section class="w-full max-w-7xl">
    @php
        $now = \App\Support\ClinicTime::now();
        $today = $this->today;
        $statusColors = ['scheduled' => 'zinc', 'confirmed' => 'sky', 'checked_in' => 'amber', 'completed' => 'green', 'cancelled' => 'red', 'no_show' => 'red'];
        $active = $today->whereNotIn('status', [\App\Enums\AppointmentStatus::Cancelled, \App\Enums\AppointmentStatus::NoShow]);
        $waiting = $today->where('status', \App\Enums\AppointmentStatus::CheckedIn);
        $done = $today->where('status', \App\Enums\AppointmentStatus::Completed);
        $setup = $this->setup;
        $setupDone = collect($setup)->where('done', true)->count();
    @endphp

    {{-- Greeting, date and the things people do most --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm text-zinc-500">{{ $now->translatedFormat('l j F Y') }}</p>
            <h1 class="text-2xl font-semibold">{{ $this->greeting() }}</h1>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($this->canManage())
                <flux:button variant="primary" icon="calendar-days" :href="route('clinic.appointments.index', ['date' => $now->format('Y-m-d')])" wire:navigate>{{ __('Book appointment') }}</flux:button>
            @endif
            @can('create', \App\Models\Patient::class)
                <flux:button icon="user-plus" :href="route('clinic.patients.create')" wire:navigate>{{ __('New patient') }}</flux:button>
            @endcan
        </div>
    </div>

    {{-- Find a patient from anywhere --}}
    @can('viewAny', \App\Models\Patient::class)
        <div class="relative mb-6 max-w-xl">
            <flux:input wire:model.live.debounce.250ms="patientSearch" icon="magnifying-glass" :placeholder="__('Find a patient: name, number or phone')" :aria-label="__('Find a patient')" autocomplete="off" />
            @if (mb_strlen(trim($patientSearch)) >= 2)
                <div class="absolute inset-x-0 top-full z-20 mt-1 overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                    @forelse ($this->searchResults as $result)
                        <a href="{{ route('clinic.patients.show', $result) }}" wire:navigate class="flex items-center justify-between gap-3 px-4 py-2.5 text-sm hover:bg-brand-soft">
                            <span class="font-medium">{{ $result->full_name }}</span>
                            <span class="text-xs text-zinc-500" dir="ltr">{{ $result->patient_number }} · {{ $result->phone }}</span>
                        </a>
                    @empty
                        <p class="px-4 py-3 text-sm text-zinc-500">{{ __('No patients found.') }}</p>
                    @endforelse
                </div>
            @endif
        </div>
    @endcan

    {{-- First-run checklist for administrators --}}
    @if ($setup !== [] && $setupDone < count($setup))
        <div class="mb-6 rounded-xl border border-brand/30 bg-brand-soft p-5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="font-semibold">{{ __('Get your clinic ready') }}</h2>
                    <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __(':done of :total steps done. This list disappears when everything is set up.', ['done' => $setupDone, 'total' => count($setup)]) }}</p>
                </div>
                <div class="h-2 w-40 overflow-hidden rounded-full bg-white dark:bg-zinc-800" role="progressbar" aria-valuemin="0" aria-valuemax="{{ count($setup) }}" aria-valuenow="{{ $setupDone }}">
                    <div class="h-full rounded-full bg-brand" style="width: {{ round($setupDone / count($setup) * 100) }}%"></div>
                </div>
            </div>
            <ol class="mt-4 grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($setup as $i => $step)
                    <li>
                        <a href="{{ $step['route'] }}" wire:navigate @class(['flex items-start gap-3 rounded-lg border bg-white p-3 text-sm dark:bg-zinc-900', 'border-zinc-200 dark:border-zinc-700 hover:border-brand' => ! $step['done'], 'border-transparent opacity-70' => $step['done']])>
                            @if ($step['done'])
                                <flux:icon.check-circle variant="solid" class="size-5 shrink-0 text-brand" />
                            @else
                                <span class="flex size-5 shrink-0 items-center justify-center rounded-full border border-zinc-300 text-xs dark:border-zinc-600">{{ $i + 1 }}</span>
                            @endif
                            <span>
                                <span @class(['block font-medium', 'line-through' => $step['done']])>{{ $step['label'] }}</span>
                                <span class="block text-xs text-zinc-500">{{ $step['help'] }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ol>
        </div>
    @endif

    {{-- Today at a glance --}}
    @php $counts = $this->counts; @endphp
    <div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <a href="{{ route('clinic.appointments.index', ['date' => $now->format('Y-m-d')]) }}" wire:navigate class="rounded-xl border border-zinc-200 p-4 hover:border-brand dark:border-zinc-700">
            <p class="flex items-center gap-2 text-sm text-zinc-500"><flux:icon.calendar-days class="size-4" />{{ $this->seesWholeClinic() ? __('Appointments today') : __('My appointments today') }}</p>
            <p class="mt-1 text-3xl font-semibold">{{ $active->count() }}</p>
            <p class="text-xs text-zinc-500">{{ __(':done done · :waiting waiting', ['done' => $done->count(), 'waiting' => $waiting->count()]) }}</p>
        </a>
        @if ($counts['requests'] !== null)
            <a href="{{ route('clinic.appointment-requests.index') }}" wire:navigate class="rounded-xl border border-zinc-200 p-4 hover:border-brand dark:border-zinc-700">
                <p class="flex items-center gap-2 text-sm text-zinc-500"><flux:icon.inbox-arrow-down class="size-4" />{{ __('Requests to call back') }}</p>
                <p @class(['mt-1 text-3xl font-semibold', 'text-amber-600' => $counts['requests'] > 0])>{{ $counts['requests'] }}</p>
                <p class="text-xs text-zinc-500">{{ __('From the website and the portal') }}</p>
            </a>
        @endif
        @if ($counts['planned'] !== null)
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <p class="flex items-center gap-2 text-sm text-zinc-500"><flux:icon.clipboard-document-list class="size-4" />{{ __('My planned treatments') }}</p>
                <p class="mt-1 text-3xl font-semibold">{{ $counts['planned'] }}</p>
                <p class="text-xs text-zinc-500">{{ __('Planned or in progress') }}</p>
            </div>
        @endif
        @if ($counts['patients_new'] !== null)
            <a href="{{ route('clinic.patients.index') }}" wire:navigate class="rounded-xl border border-zinc-200 p-4 hover:border-brand dark:border-zinc-700">
                <p class="flex items-center gap-2 text-sm text-zinc-500"><flux:icon.user-plus class="size-4" />{{ __('New patients') }}</p>
                <p class="mt-1 text-3xl font-semibold">{{ $counts['patients_new'] }}</p>
                <p class="text-xs text-zinc-500">{{ __('Last 30 days') }}</p>
            </a>
        @endif
        @if ($counts['messages'] !== null)
            <a href="{{ route('clinic.content.messages.index') }}" wire:navigate class="rounded-xl border border-zinc-200 p-4 hover:border-brand dark:border-zinc-700">
                <p class="flex items-center gap-2 text-sm text-zinc-500"><flux:icon.envelope class="size-4" />{{ __('Website messages') }}</p>
                <p @class(['mt-1 text-3xl font-semibold', 'text-amber-600' => $counts['messages'] > 0])>{{ $counts['messages'] }}</p>
                <p class="text-xs text-zinc-500">{{ __('Not yet answered') }}</p>
            </a>
        @endif
    </div>

    {{-- Next seven days --}}
    <nav aria-label="{{ __('Next seven days') }}" class="mb-6 grid grid-cols-7 gap-1 sm:gap-2">
        @foreach ($this->week as $i => $day)
            @php $d = \Carbon\CarbonImmutable::createFromFormat('Y-m-d', $day['date']); @endphp
            <a href="{{ route('clinic.appointments.index', ['date' => $day['date']]) }}" wire:navigate
               @class(['rounded-lg border px-0.5 py-2 text-center hover:border-brand',
                       'border-brand bg-brand-soft' => $i === 0,
                       'border-zinc-200 dark:border-zinc-700' => $i !== 0])>
                <span @class(['block text-xs dark:text-zinc-400', 'text-zinc-600' => $i === 0, 'text-zinc-500' => $i !== 0])>{{ $i === 0 ? __('Today') : $d->translatedFormat('D') }}</span>
                <span class="block text-lg font-semibold">{{ $d->format('j') }}</span>
                <span @class(['block text-xs', 'text-brand font-medium' => $day['count'] > 0, 'text-zinc-600 dark:text-zinc-400' => $day['count'] === 0 && $i === 0, 'text-zinc-500 dark:text-zinc-400' => $day['count'] === 0 && $i !== 0])>
                    <span class="sm:hidden">{{ $day['count'] }}</span>
                    <span class="hidden sm:inline">{{ trans_choice(':count appt|:count appts', $day['count']) }}</span>
                </span>
            </a>
        @endforeach
    </nav>

    <div class="grid gap-6 xl:grid-cols-[2fr_1fr]">
        {{-- Today's schedule --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                <h2 class="font-semibold">{{ $this->seesWholeClinic() ? __("Today's schedule") : __('My day') }}</h2>
                <flux:button size="sm" variant="ghost" icon-trailing="arrow-right" :href="route('clinic.appointments.index', ['date' => $now->format('Y-m-d')])" wire:navigate>{{ __('Open calendar') }}</flux:button>
            </div>
            @if ($today->isEmpty())
                <div class="p-8 text-center">
                    <flux:icon.calendar class="mx-auto size-8 text-zinc-300" />
                    <p class="mt-2 font-medium">{{ __('No appointments today') }}</p>
                    @if ($this->canManage())
                        <p class="text-sm text-zinc-500">{{ __('Book one from the calendar, or from a request.') }}</p>
                    @endif
                </div>
            @else
                <ol class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($today as $appointment)
                        @php
                            $start = \App\Support\ClinicTime::local($appointment->starts_at);
                            $end = \App\Support\ClinicTime::local($appointment->ends_at);
                            $isNow = $now >= $start && $now < $end;
                            $isPast = $now >= $end;
                        @endphp
                        <li wire:key="today-{{ $appointment->id }}" @class(['grid grid-cols-[3.5rem_1fr] items-center gap-x-3 gap-y-2 px-4 py-3 sm:flex sm:gap-x-4', 'bg-brand-soft' => $isNow, 'opacity-60' => in_array($appointment->status->value, ['cancelled', 'no_show'], true)])>
                            <div class="shrink-0 sm:w-20" dir="ltr">
                                <p class="font-semibold">{{ $start->format('H:i') }}</p>
                                <p class="text-xs text-zinc-500">{{ $end->format('H:i') }}</p>
                            </div>
                            <div class="min-w-0 flex-1">
                                @can('view', $appointment->patient)
                                    <a href="{{ route('clinic.patients.show', $appointment->patient) }}" wire:navigate class="font-medium hover:underline">{{ $appointment->patient->full_name }}</a>
                                @else
                                    <span class="font-medium">{{ $appointment->patient->full_name }}</span>
                                @endcan
                                <p class="truncate text-xs text-zinc-500">
                                    @if ($this->seesWholeClinic()) {{ $appointment->dentist?->name }} · @endif
                                    {{ $appointment->service?->translationOrFirst()?->title ?? __('Appointment') }}
                                    @if ($isNow) · <span class="font-medium text-brand">{{ __('Now') }}</span> @endif
                                </p>
                            </div>
                            <div class="col-span-2 flex flex-wrap items-center justify-end gap-1 sm:col-span-1">
                            <flux:badge size="sm" :color="$statusColors[$appointment->status->value]">{{ $appointment->status->label() }}</flux:badge>
                            @if ($this->canManage() && ! $appointment->status->isFinal())
                                <div class="flex gap-1">
                                    @if ($appointment->status->canTransitionTo(\App\Enums\AppointmentStatus::CheckedIn))
                                        <flux:button size="xs" variant="primary" wire:click="setStatus({{ $appointment->id }}, 'checked_in')">{{ __('Check in') }}</flux:button>
                                    @endif
                                    @if ($appointment->status->canTransitionTo(\App\Enums\AppointmentStatus::Completed))
                                        <flux:button size="xs" variant="primary" wire:click="setStatus({{ $appointment->id }}, 'completed')">{{ __('Done') }}</flux:button>
                                    @endif
                                    @if ($isPast && $appointment->status->canTransitionTo(\App\Enums\AppointmentStatus::NoShow))
                                        <flux:button size="xs" variant="ghost" wire:click="setStatus({{ $appointment->id }}, 'no_show')">{{ __('No show') }}</flux:button>
                                    @endif
                                </div>
                            @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>

        <div class="space-y-6">
            @if ($this->requests->isNotEmpty())
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                        <h2 class="font-semibold">{{ __('Requests to call back') }}</h2>
                        <flux:button size="sm" variant="ghost" icon-trailing="arrow-right" :href="route('clinic.appointment-requests.index')" wire:navigate>{{ __('All') }}</flux:button>
                    </div>
                    <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($this->requests as $request)
                            <li class="px-4 py-3 text-sm">
                                <p class="font-medium">{{ $request->name }}</p>
                                <p class="text-xs text-zinc-500">
                                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $request->phone) }}" class="underline" dir="ltr">{{ $request->phone }}</a>
                                    · {{ $request->service?->translationOrFirst()?->title ?? __('Not specified') }}
                                    · {{ $request->created_at?->diffForHumans() }}
                                </p>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($this->myPlanned->isNotEmpty())
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                        <h2 class="font-semibold">{{ __('My planned treatments') }}</h2>
                    </div>
                    <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($this->myPlanned as $item)
                            <li>
                                <a href="{{ route('clinic.patients.show', ['patient' => $item->patient_id, 'tab' => 'treatments', 'tooth' => $item->tooth_number]) }}" wire:navigate class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-brand-soft">
                                    <span aria-hidden="true" class="size-3 shrink-0 rounded-sm" style="background-color: {{ $item->procedure->displayColor() }}"></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate font-medium">{{ $item->patient?->full_name }}</span>
                                        <span class="block truncate text-xs text-zinc-500">{{ $item->procedure->localizedName() }}@if ($item->tooth_number) · {{ $item->tooth_number }}@endif @if ($item->planned_for) · {{ $item->planned_for->format('Y-m-d') }}@endif</span>
                                    </span>
                                    <x-ui.status-badge :status="$item->status" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($this->requests->isEmpty() && $this->myPlanned->isEmpty())
                <div class="rounded-xl border border-dashed border-zinc-300 p-5 text-sm dark:border-zinc-700">
                    <h2 class="font-semibold">{{ __('Tips') }}</h2>
                    <ul class="mt-2 list-disc space-y-1 ps-5 text-zinc-600 dark:text-zinc-300">
                        <li>{{ __('Open a patient to see their chart, history and notes in one place.') }}</li>
                        <li>{{ __('Click a tooth on the chart to add a treatment for it.') }}</li>
                        <li>{{ __('Use the calendar to book: free times are shown for each dentist.') }}</li>
                    </ul>
                </div>
            @endif
        </div>
    </div>
</section>
