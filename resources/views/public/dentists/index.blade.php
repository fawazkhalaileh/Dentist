<x-layouts::public :title="__('Meet the dentists')" :description="__('The dental team at :clinic.', ['clinic' => \App\Support\ClinicDetails::name()])">
    <x-ui.page-header :title="__('Meet the dentists')" />

    @if ($dentists->isEmpty())
        <x-ui.empty-state :title="__('Dentist profiles are being prepared')" />
    @else
        <ul class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($dentists as $profile)
                <li><x-public.dentist-card :profile="$profile" /></li>
            @endforeach
        </ul>
    @endif
</x-layouts::public>
