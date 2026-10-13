<x-layouts::portal :title="__('Patient portal')">
    <x-ui.page-header :title="__('Welcome, :name', ['name' => auth()->user()->name])" />

    @if ($patient)
        <dl class="grid max-w-md grid-cols-2 gap-2 text-sm">
            <dt class="text-ink-muted dark:text-zinc-400">{{ __('Patient number') }}</dt>
            <dd class="font-medium" data-test="patient-number">{{ $patient->patient_number }}</dd>
        </dl>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700" data-test="next-appointment">
                <h2 class="font-semibold">{{ __('Next appointment') }}</h2>
                @if ($next)
                    @php $local = \App\Support\ClinicTime::local($next->starts_at); @endphp
                    <p class="mt-2">{{ $local->translatedFormat('l j F Y') }} · <span dir="ltr">{{ $local->format('H:i') }}</span></p>
                    <p class="text-sm text-ink-muted dark:text-zinc-400">{{ $next->dentist?->name }}@if ($next->service) · {{ $next->service->translationOrFirst()?->title }}@endif</p>
                @else
                    <p class="mt-2 text-sm text-ink-muted dark:text-zinc-400">{{ __('You have no upcoming appointments.') }}</p>
                @endif
                <a href="{{ route('portal.appointments') }}" class="mt-3 inline-block text-sm underline">{{ __('My appointments') }}</a>
            </div>
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <h2 class="font-semibold">{{ __('My treatments') }}</h2>
                <p class="mt-2 text-sm text-ink-muted dark:text-zinc-400">{{ __('See treatments planned or carried out at the clinic.') }}</p>
                <a href="{{ route('portal.treatments') }}" class="mt-3 inline-block text-sm underline">{{ __('My treatments') }}</a>
            </div>
        </div>
    @else
        <x-ui.empty-state
            :title="__('Your account is not linked to a patient record yet')"
            :description="__('The clinic links your account to your patient record after verifying your identity. Contact reception if you are already a patient.')"
            data-test="patient-not-linked"
        />
    @endif
</x-layouts::portal>
