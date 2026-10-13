<?php

use App\Actions\Scheduling\ChangeAppointmentStatus;
use App\Enums\AppointmentRequestStatus;
use App\Enums\AppointmentStatus;
use App\Livewire\PortalComponent;
use App\Models\Appointment;
use App\Models\AppointmentRequest;
use App\Models\Service;
use App\Models\StaffProfile;
use App\Support\ClinicTime;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;

new #[Title('My appointments')] class extends PortalComponent {
    #[Locked]
    public ?int $cancellingId = null;

    public string $cancelReason = '';

    public bool $requesting = false;

    /** @var array{phone: string, service: string, dentist: string, preferred_date: string, preferred_time: string, message: string} */
    public array $request = ['phone' => '', 'service' => '', 'dentist' => '', 'preferred_date' => '', 'preferred_time' => 'any', 'message' => ''];

    /**
     * @return Collection<int, Appointment>
     */
    #[Computed]
    public function appointments(): Collection
    {
        return $this->patient()?->appointments()->with(['dentist:id,name', 'service.translations'])->latest('starts_at')->get() ?? new Collection;
    }

    /**
     * @return Collection<int, AppointmentRequest>
     */
    #[Computed]
    public function pendingRequests(): Collection
    {
        $patient = $this->patient();

        return $patient
            ? AppointmentRequest::where('patient_id', $patient->id)->where('status', AppointmentRequestStatus::Pending->value)->latest('id')->get()
            : new Collection;
    }

    /**
     * @return Collection<int, Service>
     */
    #[Computed]
    public function services(): Collection
    {
        return Service::published()->translatedIn()->with('translations')->orderBy('sort_order')->get();
    }

    /**
     * @return Collection<int, StaffProfile>
     */
    #[Computed]
    public function dentists(): Collection
    {
        return StaffProfile::shownPublicly()->translatedIn()->with('translations')->orderBy('sort_order')->get();
    }

    public function startCancel(int $id): void
    {
        $this->authorize('cancel', $this->ownAppointment($id));
        $this->cancellingId = $id;
        $this->cancelReason = '';
        $this->resetValidation();
    }

    public function keepAppointment(): void
    {
        $this->cancellingId = null;
    }

    public function confirmCancel(ChangeAppointmentStatus $change): void
    {
        abort_if($this->cancellingId === null, 422);
        $appointment = $this->ownAppointment($this->cancellingId);
        $this->authorize('cancel', $appointment);
        $this->validate(['cancelReason' => ['required', 'string', 'max:255']], [], ['cancelReason' => __('reason')]);

        $change->handle($appointment, AppointmentStatus::Cancelled, $this->account(), $this->cancelReason);

        $this->cancellingId = null;
        unset($this->appointments);
        Flux::toast(variant: 'success', text: __('Your appointment has been cancelled.'));
    }

    public function openRequest(): void
    {
        abort_if($this->patient() === null, 403);
        $this->resetValidation();
        $this->request = ['phone' => (string) $this->patient()?->phone, 'service' => '', 'dentist' => '', 'preferred_date' => '', 'preferred_time' => 'any', 'message' => ''];
        $this->requesting = true;
    }

    public function sendRequest(): void
    {
        $patient = $this->patient();
        abort_if($patient === null, 403);

        $this->validate([
            'request.phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\-\s.]{6,}$/'],
            'request.service' => ['nullable', Rule::in(['', ...$this->services->pluck('id')->map(fn ($id) => (string) $id)->all()])],
            'request.dentist' => ['nullable', Rule::in(['', ...$this->dentists->pluck('user_id')->map(fn ($id) => (string) $id)->all()])],
            'request.preferred_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:'.ClinicTime::today()],
            'request.preferred_time' => ['required', Rule::in(AppointmentRequest::PREFERRED_TIMES)],
            'request.message' => ['nullable', 'string', 'max:2000'],
        ]);

        AppointmentRequest::create([
            'patient_id' => $patient->id,
            'name' => $patient->full_name,
            'phone' => trim($this->request['phone']),
            'email' => $patient->email ?? $this->account()->email,
            'preferred_dentist_id' => $this->request['dentist'] !== '' ? (int) $this->request['dentist'] : null,
            'service_id' => $this->request['service'] !== '' ? (int) $this->request['service'] : null,
            'preferred_date' => $this->request['preferred_date'] ?: null,
            'preferred_time' => $this->request['preferred_time'],
            'message' => trim($this->request['message']) ?: null,
            'locale' => app()->getLocale(),
        ]);

        $this->requesting = false;
        unset($this->pendingRequests);
        Flux::toast(variant: 'success', text: __('Request sent. The clinic will call you to agree a time.'));
    }

    /**
     * One of the signed-in patient's own appointments; anything else is a 404.
     */
    private function ownAppointment(int $id): Appointment
    {
        $patient = $this->patient();
        abort_if($patient === null, 404);

        return $patient->appointments()->findOrFail($id);
    }
}; ?>

<section class="w-full max-w-3xl">
    <x-ui.page-header :title="__('My appointments')">
        <x-slot:actions>
            @if (auth()->user()->patient)
                <flux:button variant="primary" icon="plus" wire:click="openRequest">{{ __('Request an appointment') }}</flux:button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    @if (! auth()->user()->patient)
        <x-ui.empty-state :title="__('Your account is not linked to a patient record yet')" :description="__('The clinic links your account to your patient record after verifying your identity. Contact reception if you are already a patient.')" />
    @else
        @if ($requesting)
            <flux:card class="mb-6">
                <form wire:submit="sendRequest" class="space-y-4">
                    <flux:text class="text-sm">{{ __('This is a request, not a confirmed booking: the clinic will call you to agree a time.') }}</flux:text>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:input wire:model="request.phone" type="tel" :label="__('Phone')" dir="ltr" />
                        <flux:input wire:model="request.preferred_date" type="date" :label="__('Preferred date')" min="{{ \App\Support\ClinicTime::today() }}" />
                        @if ($this->services->isNotEmpty())
                            <flux:select wire:model="request.service" :label="__('Treatment')">
                                <flux:select.option value="">{{ __('Not sure / check-up') }}</flux:select.option>
                                @foreach ($this->services as $service)
                                    <flux:select.option value="{{ $service->id }}">{{ $service->translation()?->title }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        @endif
                        @if ($this->dentists->isNotEmpty())
                            <flux:select wire:model="request.dentist" :label="__('Preferred dentist')">
                                <flux:select.option value="">{{ __('No preference') }}</flux:select.option>
                                @foreach ($this->dentists as $profile)
                                    <flux:select.option value="{{ $profile->user_id }}">{{ $profile->translation()?->display_name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        @endif
                    </div>
                    <flux:radio.group wire:model="request.preferred_time" :label="__('Preferred time')" variant="segmented">
                        @foreach (\App\Models\AppointmentRequest::PREFERRED_TIMES as $time)
                            <flux:radio value="{{ $time }}" :label="\App\Models\AppointmentRequest::preferredTimeLabel($time)" />
                        @endforeach
                    </flux:radio.group>
                    <flux:textarea wire:model="request.message" :label="__('Anything we should know?')" rows="3" dir="auto" />
                    <div class="flex gap-2">
                        <flux:button type="submit" variant="primary">{{ __('Send request') }}</flux:button>
                        <flux:button wire:click="$set('requesting', false)">{{ __('Cancel') }}</flux:button>
                    </div>
                </form>
            </flux:card>
        @endif

        @if ($this->pendingRequests->isNotEmpty())
            <flux:callout icon="clock" class="mb-6">
                <flux:callout.text>{{ trans_choice('You have :count request waiting for the clinic to call you.|You have :count requests waiting for the clinic to call you.', $this->pendingRequests->count()) }}</flux:callout.text>
            </flux:callout>
        @endif

        @php $now = now(); @endphp
        @foreach (['upcoming' => __('Upcoming'), 'past' => __('Past')] as $group => $heading)
            @php $list = $this->appointments->filter(fn ($a) => $group === 'upcoming' ? $a->starts_at >= $now : $a->starts_at < $now); @endphp
            <flux:heading size="lg" class="mb-2 mt-6">{{ $heading }}</flux:heading>
            @if ($list->isEmpty())
                <flux:text class="text-sm">{{ $group === 'upcoming' ? __('No upcoming appointments.') : __('No past appointments.') }}</flux:text>
            @else
                <ul class="space-y-3">
                    @foreach ($group === 'upcoming' ? $list->sortBy('starts_at') : $list as $appointment)
                        @php $local = \App\Support\ClinicTime::local($appointment->starts_at); @endphp
                        <li wire:key="my-{{ $appointment->id }}">
                            <flux:card class="space-y-2">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="font-medium">{{ $local->translatedFormat('l j F Y') }} · <span dir="ltr">{{ $local->format('H:i') }}</span></span>
                                    <flux:badge size="sm">{{ $appointment->status->label() }}</flux:badge>
                                </div>
                                <p class="text-sm">
                                    {{ $appointment->dentist?->name }}
                                    @if ($appointment->service) · {{ $appointment->service->translationOrFirst()?->title }} @endif
                                </p>
                                @can('cancel', $appointment)
                                    @if (! $appointment->status->isFinal() && $cancellingId !== $appointment->id)
                                        <flux:button size="xs" variant="ghost" wire:click="startCancel({{ $appointment->id }})">{{ __('Cancel appointment') }}</flux:button>
                                    @endif
                                @endcan
                                @if ($cancellingId === $appointment->id)
                                    <form wire:submit="confirmCancel" class="space-y-2">
                                        <flux:input wire:model="cancelReason" :label="__('Reason for cancelling')" />
                                        <div class="flex gap-2">
                                            <flux:button size="sm" type="submit" variant="danger">{{ __('Cancel appointment') }}</flux:button>
                                            <flux:button size="sm" wire:click="keepAppointment">{{ __('Keep it') }}</flux:button>
                                        </div>
                                    </form>
                                @endif
                            </flux:card>
                        </li>
                    @endforeach
                </ul>
            @endif
        @endforeach
    @endif
</section>
