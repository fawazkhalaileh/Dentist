<x-layouts::public :title="__('Our services')" :description="__('Dental services offered at :clinic.', ['clinic' => \App\Support\ClinicDetails::name()])">
    <x-ui.page-header :title="__('Our services')" />

    @if ($services->isEmpty())
        <x-ui.empty-state :title="__('No services published yet')" :description="__('Please check back soon or contact the clinic.')" />
    @else
        <ul class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($services as $service)
                <li><x-public.service-card :service="$service" /></li>
            @endforeach
        </ul>
    @endif
</x-layouts::public>
