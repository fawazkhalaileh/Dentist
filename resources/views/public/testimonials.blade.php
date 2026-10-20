<x-layouts::public :title="__('Testimonials')" :bleed="true" :description="__('What patients say about their visits to :clinic.', ['clinic' => \App\Support\ClinicDetails::name()])">
    <x-site.page-hero :title="__('What our patients say')" :eyebrow="__('Patient stories')" />

    <div class="site-container py-10 md:py-16">
        @if ($testimonials->isEmpty())
            <x-ui.empty-state :title="__('No testimonials published yet')" />
        @else
            <ul class="grid gap-6 md:grid-cols-2">
                @foreach ($testimonials as $testimonial)
                    <li>
                        <figure class="flex h-full flex-col rounded-[2rem] bg-cream p-8 md:p-10">
                            <x-site.icon name="quote" class="size-9 text-wine/70" />
                            @if ($testimonial->rating)
                                <p class="mt-4 text-sm text-wine" aria-label="{{ __('Rating: :n out of 5', ['n' => $testimonial->rating]) }}">
                                    <span aria-hidden="true">{{ str_repeat('★', $testimonial->rating) }}{{ str_repeat('☆', 5 - $testimonial->rating) }}</span>
                                </p>
                            @endif
                            <blockquote class="mt-4 flex-1 font-display text-2xl leading-snug">{{ $testimonial->body }}</blockquote>
                            <figcaption class="mt-8 border-t border-line pt-5 text-sm font-medium">{{ $testimonial->author_name }}</figcaption>
                        </figure>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-layouts::public>
