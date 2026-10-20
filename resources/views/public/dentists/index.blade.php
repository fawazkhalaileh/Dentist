<x-layouts::public :title="__('Meet the dentists')" :bleed="true" :description="__('The dental team at :clinic.', ['clinic' => \App\Support\ClinicDetails::name()])">
    <x-site.page-hero :title="__('Meet the dentists')" :eyebrow="__('Our team')" />

    <div class="site-container py-10 md:py-16">
        @if ($dentists->isEmpty())
            <div class="grid items-center gap-10 overflow-hidden rounded-[2rem] bg-night text-ivory md:grid-cols-2">
                <x-site.photo name="doctor" sizes="(min-width: 768px) 50vw, 100vw" class="aspect-[4/3] size-full object-cover object-[58%_40%]" />
                <div class="p-8 md:p-12">
                    <h2 class="text-4xl">{{ \App\Support\Site\SiteContent::doctorName() }}</h2>
                    <a href="{{ route('public.doctor') }}" class="btn btn-light mt-8">{{ __('Meet the Doctor') }}</a>
                </div>
            </div>
        @else
            <ul class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($dentists as $profile)
                    <li><x-public.dentist-card :profile="$profile" /></li>
                @endforeach
            </ul>
        @endif
    </div>
</x-layouts::public>
