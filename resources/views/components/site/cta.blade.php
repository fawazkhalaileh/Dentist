{{-- Appointment call to action. Uses the existing request form; no new booking flow. --}}
<section aria-labelledby="cta-heading" {{ $attributes->class('px-4 py-16 md:py-24') }}>
    <div class="relative mx-auto max-w-[78rem] overflow-hidden rounded-[2rem] bg-wine px-6 py-16 text-center text-ivory md:px-16 md:py-24">
        <x-site.photo name="reception" alt="" sizes="100vw" class="absolute inset-0 size-full object-cover opacity-[0.12] mix-blend-luminosity" />
        <div aria-hidden="true" class="absolute -end-24 -top-24 size-80 rounded-full border border-ivory/15"></div>
        <div aria-hidden="true" class="absolute -bottom-32 -start-16 size-96 rounded-full border border-ivory/10"></div>
        <div class="relative mx-auto max-w-2xl">
            <h2 id="cta-heading" class="text-4xl leading-tight md:text-6xl">{{ __('Ready to Feel Confident About Your Smile?') }}</h2>
            <p class="mx-auto mt-6 max-w-xl text-lg text-ivory/80">{{ __('Take the first step towards personalised dental care. Contact our team to discuss your needs.') }}</p>
            <div class="mt-10 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ route('public.booking') }}" class="btn btn-light">{{ __('Book Your Visit') }}</a>
                <a href="{{ route('public.contact') }}" class="btn btn-ghost-light">{{ __('Contact Us') }}</a>
            </div>
        </div>
    </div>
</section>
