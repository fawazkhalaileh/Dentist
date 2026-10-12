<x-layouts::public :title="__('Frequently asked questions')" :description="__('Answers to common questions about visiting :clinic.', ['clinic' => \App\Support\ClinicDetails::name()])" :schema="$schema">
    <x-ui.page-header :title="__('Frequently asked questions')" />

    @if ($faqs->isEmpty())
        <x-ui.empty-state :title="__('No questions published yet')" :description="__('Please contact the clinic with any question.')" />
    @else
        <div class="max-w-3xl divide-y divide-line rounded-lg border border-line">
            @foreach ($faqs as $faq)
                <details class="p-4">
                    <summary class="cursor-pointer font-medium">{{ $faq->question }}</summary>
                    <x-ui.prose :markdown="$faq->answer" class="mt-2" />
                </details>
            @endforeach
        </div>
    @endif
</x-layouts::public>
