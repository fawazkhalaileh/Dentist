<x-layouts::portal :title="__('Patient portal')">
    <x-ui.page-header :title="__('Welcome, :name', ['name' => auth()->user()->name])" />

    @if ($patient)
        <dl class="grid max-w-md grid-cols-2 gap-2 text-sm">
            <dt class="text-ink-muted dark:text-zinc-400">{{ __('Patient number') }}</dt>
            <dd class="font-medium" data-test="patient-number">{{ $patient->patient_number }}</dd>
        </dl>

        <x-ui.not-built :phase="5" class="mt-6" />
    @else
        <x-ui.empty-state
            :title="__('Your account is not linked to a patient record yet')"
            :description="__('The clinic links your account to your patient record after verifying your identity. Contact reception if you are already a patient.')"
            data-test="patient-not-linked"
        />
    @endif
</x-layouts::portal>
