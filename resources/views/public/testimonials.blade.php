<x-layouts::public :title="__('Testimonials')" :description="__('What patients say about their visits to :clinic.', ['clinic' => \App\Support\ClinicDetails::name()])">
    <x-ui.page-header :title="__('What our patients say')" />

    @if ($testimonials->isEmpty())
        <x-ui.empty-state :title="__('No testimonials published yet')" />
    @else
        <ul class="grid gap-5 md:grid-cols-2">
            @foreach ($testimonials as $testimonial)
                <li>
                    <figure class="h-full rounded-lg border border-line p-5">
                        @if ($testimonial->rating)
                            <p class="mb-2 text-sm" aria-label="{{ __('Rating: :n out of 5', ['n' => $testimonial->rating]) }}">
                                <span aria-hidden="true">{{ str_repeat('★', $testimonial->rating) }}{{ str_repeat('☆', 5 - $testimonial->rating) }}</span>
                            </p>
                        @endif
                        <blockquote>{{ $testimonial->body }}</blockquote>
                        <figcaption class="mt-3 text-sm font-medium">— {{ $testimonial->author_name }}</figcaption>
                    </figure>
                </li>
            @endforeach
        </ul>
    @endif
</x-layouts::public>
