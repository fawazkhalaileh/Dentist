{{-- Appointment call to action. Uses the existing request form; no new booking flow. --}}
<section aria-labelledby="cta-heading" {{ $attributes->class('px-4 py-10 md:py-16') }}>
    <div class="relative mx-auto max-w-[78rem] overflow-hidden rounded-[2rem] bg-wine px-6 py-10 text-ivory md:px-14 md:py-14">
        <x-site.photo name="reception" alt="" sizes="100vw" class="absolute inset-0 size-full object-cover opacity-[0.12] mix-blend-luminosity" />
        <div aria-hidden="true" class="absolute -end-24 -top-24 size-80 rounded-full border border-ivory/15"></div>
        <div class="relative flex flex-col gap-7 md:flex-row md:items-center md:justify-between md:gap-12">
            <div class="max-w-2xl">
                <h2 id="cta-heading" class="text-3xl leading-tight md:text-5xl">{{ __('Ready to Feel Confident About Your Smile?') }}</h2>
                <p class="mt-3 text-ivory/80 md:text-lg">{{ __('Take the first step towards personalised dental care. Contact our team to discuss your needs.') }}</p>
            </div>
            <div class="flex shrink-0 flex-col gap-3 sm:flex-row md:flex-col lg:flex-row">
                <a href="{{ route('public.booking') }}" class="btn btn-light">{{ __('Book Your Visit') }}</a>
                <a href="{{ route('public.contact') }}" class="btn btn-ghost-light">{{ __('Contact Us') }}</a>
            </div>
        </div>
    </div>
</section>
