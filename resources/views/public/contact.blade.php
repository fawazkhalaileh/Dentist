@use('App\Support\ClinicDetails')

@php
    $phone = ClinicDetails::get('phone');
    $email = ClinicDetails::get('email');
    $whatsapp = ClinicDetails::get('whatsapp');
    $address = ClinicDetails::get('address');
    $hours = ClinicDetails::get('opening_hours');
    $mapUrl = ClinicDetails::get('map_url');
    // Only an official Google Maps embed address is shown as a map; anything else becomes a link.
    $mapEmbed = $mapUrl && str_starts_with($mapUrl, 'https://www.google.com/maps/embed') ? $mapUrl : null;
    $mapLink = $mapUrl ?: ($address ? 'https://www.google.com/maps/search/?api=1&query='.urlencode($address) : null);
@endphp

<x-layouts::public :title="__('Contact')" :bleed="true" :description="__('Contact details and opening hours for :clinic.', ['clinic' => ClinicDetails::name()])" :schema="$schema">
    <x-site.page-hero :title="__('Contact us')" :eyebrow="__('We are here to help')"
        :intro="__('Questions about a treatment, or ready to book? Get in touch and our team will get back to you.')" />

    <section class="py-16 md:py-24">
        <div class="site-container grid gap-12 lg:grid-cols-[1fr_1.15fr] lg:gap-16">
            <section aria-labelledby="contact-details">
                <h2 id="contact-details" class="text-4xl">{{ __('Visit or call') }}</h2>
                <ul class="mt-8 grid gap-4 sm:grid-cols-2">
                    <li class="rounded-[1.5rem] border border-line bg-white/60 p-6 sm:col-span-2">
                        <x-site.icon name="pin" class="size-6 text-wine" />
                        <p class="mt-4 text-sm font-medium uppercase tracking-[0.12em] text-stone">{{ __('Address') }}</p>
                        @if ($address)
                            <p class="mt-2 whitespace-pre-line text-lg">{{ $address }}</p>
                            @if ($mapLink)
                                <a href="{{ $mapLink }}" rel="noopener" target="_blank" class="link-arrow mt-3 text-sm">{{ __('Open in Maps') }} <x-site.icon name="arrow" class="arrow size-4" /><span class="sr-only"> ({{ __('opens in a new tab') }})</span></a>
                            @endif
                        @else
                            <p class="mt-2 text-lg italic text-ink-muted">{{ __('Address to be confirmed') }}</p>
                        @endif
                    </li>
                    <li class="rounded-[1.5rem] border border-line bg-white/60 p-6">
                        <x-site.icon name="phone" class="size-6 text-wine" />
                        <p class="mt-4 text-sm font-medium uppercase tracking-[0.12em] text-stone">{{ __('Phone') }}</p>
                        @if ($phone)
                            <a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" class="mt-2 block text-lg hover:text-wine" dir="ltr">{{ $phone }}</a>
                        @else
                            <p class="mt-2 text-lg italic text-ink-muted">{{ __('Phone number coming soon') }}</p>
                        @endif
                        @if ($whatsapp)
                            <a href="https://wa.me/{{ preg_replace('/\D/', '', $whatsapp) }}" rel="noopener" class="mt-2 inline-flex items-center gap-2 text-sm text-wine underline underline-offset-4"><x-site.icon name="whatsapp" class="size-4" />{{ __('WhatsApp') }} <span dir="ltr">{{ $whatsapp }}</span></a>
                        @endif
                    </li>
                    <li class="rounded-[1.5rem] border border-line bg-white/60 p-6">
                        <x-site.icon name="mail" class="size-6 text-wine" />
                        <p class="mt-4 text-sm font-medium uppercase tracking-[0.12em] text-stone">{{ __('Email') }}</p>
                        @if ($email)
                            <a href="mailto:{{ $email }}" class="mt-2 block break-all text-lg hover:text-wine">{{ $email }}</a>
                        @else
                            <p class="mt-2 text-lg italic text-ink-muted">{{ __('Use the form on this page') }}</p>
                        @endif
                    </li>
                    <li class="rounded-[1.5rem] bg-cream p-6 sm:col-span-2">
                        <x-site.icon name="clock" class="size-6 text-wine" />
                        <p class="mt-4 text-sm font-medium uppercase tracking-[0.12em] text-stone">{{ __('Opening hours') }}</p>
                        <p class="mt-2 whitespace-pre-line text-lg">{{ $hours ?: __('Opening hours coming soon') }}</p>
                    </li>
                </ul>

                @if ($mapEmbed)
                    <iframe src="{{ $mapEmbed }}" title="{{ __('Map showing the clinic location') }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" class="mt-6 aspect-[4/3] w-full rounded-[1.5rem] border-0"></iframe>
                @endif

                <div class="mt-8 flex items-center gap-3">
                    <span class="text-sm text-ink-muted">{{ __('Follow us') }}</span>
                    @foreach (config('site.social') as $network => $url)
                        <a href="{{ $url }}" rel="noopener" target="_blank" class="flex size-10 items-center justify-center rounded-full border border-line text-wine hover:border-wine">
                            <x-site.icon :name="$network" class="size-5" /><span class="sr-only">{{ ucfirst($network) }} ({{ __('opens in a new tab') }})</span>
                        </a>
                    @endforeach
                </div>
            </section>

            <section aria-labelledby="contact-form-heading" class="rounded-[2rem] bg-white p-7 shadow-[0_30px_70px_-45px_rgb(34_27_29/0.45)] md:p-10">
                <h2 id="contact-form-heading" class="text-4xl">{{ __('Send us a message') }}</h2>
                <p class="mt-3 text-sm text-ink-muted">{{ __('For medical emergencies, please call the clinic or emergency services instead.') }}</p>

                @if (session('status') === 'contact-sent')
                    <div role="status" class="mt-6 rounded-2xl border border-status-planned bg-status-planned/5 p-4 text-sm" data-test="contact-sent">
                        {{ __('Thank you. Your message has been sent and the clinic will reply soon.') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('public.contact.store') }}" class="mt-8 space-y-5" novalidate>
                    @csrf

                    {{-- Honeypot: hidden from people and assistive technology --}}
                    <div class="hidden" aria-hidden="true">
                        <label for="website">Website</label>
                        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div>
                        <label for="name" class="text-sm font-medium">{{ __('Name') }}</label>
                        <input id="name" name="name" type="text" required autocomplete="name" value="{{ old('name') }}" class="field"
                               @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                        @error('name') <p id="name-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="email" class="text-sm font-medium">{{ __('Email') }}</label>
                            <input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" class="field"
                                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                            @error('email') <p id="email-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="phone" class="text-sm font-medium">{{ __('Phone') }}</label>
                            <input id="phone" name="phone" type="tel" autocomplete="tel" dir="ltr" value="{{ old('phone') }}" class="field"
                                   @error('phone') aria-invalid="true" aria-describedby="phone-error" @enderror>
                            @error('phone') <p id="phone-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label for="message" class="text-sm font-medium">{{ __('Message') }}</label>
                        <textarea id="message" name="message" rows="5" required maxlength="3000" class="field"
                                  @error('message') aria-invalid="true" aria-describedby="message-error" @enderror>{{ old('message') }}</textarea>
                        @error('message') <p id="message-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-full sm:w-auto">{{ __('Send message') }}</button>
                </form>

                <div class="mt-10 flex flex-col gap-4 border-t border-line pt-8 sm:flex-row sm:items-center sm:justify-between">
                    <p class="font-display text-2xl">{{ __('Prefer to book directly?') }}</p>
                    <a href="{{ route('public.booking') }}" class="btn btn-outline">{{ __('Book an Appointment') }}</a>
                </div>
            </section>
        </div>
    </section>
</x-layouts::public>
