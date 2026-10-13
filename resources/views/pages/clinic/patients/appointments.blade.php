<?php

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Patient appointments')] class extends Component {
    public Patient $patient;

    public function mount(Patient $patient): void
    {
        $this->authorize('view', $patient);
        $this->patient = $patient;
    }

    /**
     * Appointments this user may see: all for front-desk staff, own for a dentist.
     *
     * @return Collection<int, Appointment>
     */
    #[Computed]
    public function appointments(): Collection
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $this->patient->appointments()
            ->with(['dentist:id,name', 'service.translations'])
            ->latest('starts_at')
            ->get()
            ->filter(fn (Appointment $a) => $user->can('view', $a))
            ->values();
    }
}; ?>

<section class="w-full max-w-4xl">
    <x-ui.page-header :title="__('Appointments')" :description="$patient->full_name.' · '.$patient->patient_number">
        <x-slot:actions>
            @can('create', \App\Models\Appointment::class)
                <flux:button variant="primary" icon="plus" :href="route('clinic.appointments.index')" wire:navigate>{{ __('Book in the calendar') }}</flux:button>
            @endcan
            <flux:button :href="route('clinic.patients.show', $patient)" wire:navigate>{{ __('Back to patient') }}</flux:button>
        </x-slot:actions>
    </x-ui.page-header>

    @php $now = now(); @endphp
    @foreach (['upcoming' => __('Upcoming'), 'past' => __('Past')] as $group => $heading)
        @php $list = $this->appointments->filter(fn ($a) => $group === 'upcoming' ? $a->starts_at >= $now : $a->starts_at < $now); @endphp
        <flux:heading size="lg" class="mb-2 mt-6">{{ $heading }}</flux:heading>
        @if ($list->isEmpty())
            <flux:text class="text-sm">{{ $group === 'upcoming' ? __('No upcoming appointments.') : __('No past appointments.') }}</flux:text>
        @else
            <ul class="divide-y divide-zinc-200 rounded-lg border border-zinc-200 text-sm dark:divide-zinc-700 dark:border-zinc-700">
                @foreach ($group === 'upcoming' ? $list->sortBy('starts_at') : $list as $appointment)
                    @php $local = \App\Support\ClinicTime::local($appointment->starts_at); @endphp
                    <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-3" wire:key="pa-{{ $appointment->id }}">
                        <span>
                            <a href="{{ route('clinic.appointments.index', ['date' => $local->format('Y-m-d')]) }}" wire:navigate class="font-medium underline" dir="ltr">{{ $local->format('Y-m-d H:i') }}</a>
                            · {{ $appointment->dentist?->name }}
                            @if ($appointment->service) · {{ $appointment->service->translationOrFirst()?->title }} @endif
                            @if ($appointment->cancellation_reason) <span class="block text-xs text-zinc-500">{{ __('Reason') }}: {{ $appointment->cancellation_reason }}</span> @endif
                        </span>
                        <flux:badge size="sm">{{ $appointment->status->label() }}</flux:badge>
                    </li>
                @endforeach
            </ul>
        @endif
    @endforeach
</section>
