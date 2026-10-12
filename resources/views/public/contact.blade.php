@use('App\Support\ClinicDetails')

@php
    $phone = ClinicDetails::get('phone');
    $email = ClinicDetails::get('email');
    $whatsapp = ClinicDetails::get('whatsapp');
    $address = ClinicDetails::get('address');
    $hours = ClinicDetails::get('opening_hours');
    $mapUrl = ClinicDetails::get('map_url');
    $field = 'mt-1 block w-full rounded-md border border-line bg-surface px-3 py-2';
@endphp

<x-layouts::public :title="__('Contact')" :description="__('Contact details and opening hours for :clinic.', ['clinic' => config('clinic.name')])" :schema="$schema">
    <x-ui.page-header :title="__('Contact us')" />

    <div class="grid gap-10 md:grid-cols-2">
        <section aria-labelledby="contact-details">
            <h2 id="contact-details" class="sr-only">{{ __('Contact details') }}</h2>
            <dl class="space-y-4">
                @if ($phone)
                    <div><dt class="text-sm text-ink-muted">{{ __('Phone') }}</dt><dd><a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" class="underline" dir="ltr">{{ $phone }}</a></dd></div>
                @endif
                @if ($whatsapp)
                    <div><dt class="text-sm text-ink-muted">{{ __('WhatsApp') }}</dt><dd><a href="https://wa.me/{{ preg_replace('/\D/', '', $whatsapp) }}" class="underline" dir="ltr" rel="noopener">{{ $whatsapp }}</a></dd></div>
                @endif
                @if ($email)
                    <div><dt class="text-sm text-ink-muted">{{ __('Email') }}</dt><dd><a href="mailto:{{ $email }}" class="underline">{{ $email }}</a></dd></div>
                @endif
                @if ($address)
                    <div><dt class="text-sm text-ink-muted">{{ __('Address') }}</dt><dd class="whitespace-pre-line">{{ $address }}</dd></div>
                @endif
                @if ($hours)
                    <div><dt class="text-sm text-ink-muted">{{ __('Opening hours') }}</dt><dd class="whitespace-pre-line">{{ $hours }}</dd></div>
                @endif
                @if ($mapUrl)
                    <div><dd><a href="{{ $mapUrl }}" class="underline" rel="noopener" target="_blank">{{ __('Open in maps') }}</a></dd></div>
                @endif
            </dl>

            @unless ($phone || $email || $address)
                <x-ui.empty-state :title="__('Contact details are being updated')" />
            @endunless
        </section>

        <section aria-labelledby="contact-form-heading">
            <h2 id="contact-form-heading" class="text-xl font-semibold">{{ __('Send us a message') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('For medical emergencies, please call the clinic or emergency services instead.') }}</p>

            @if (session('status') === 'contact-sent')
                <div role="status" class="mt-4 rounded-md border border-status-planned p-4 text-sm" data-test="contact-sent">
                    {{ __('Thank you. Your message has been sent and the clinic will reply soon.') }}
                </div>
            @endif

            <form method="POST" action="{{ route('public.contact.store') }}" class="mt-4 space-y-4" novalidate>
                @csrf

                {{-- Honeypot: hidden from people and assistive technology --}}
                <div class="hidden" aria-hidden="true">
                    <label for="website">Website</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <div>
                    <label for="name" class="text-sm font-medium">{{ __('Name') }}</label>
                    <input id="name" name="name" type="text" required autocomplete="name" value="{{ old('name') }}" class="{{ $field }}"
                           @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                    @error('name') <p id="name-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="email" class="text-sm font-medium">{{ __('Email') }}</label>
                    <input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" class="{{ $field }}"
                           @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                    @error('email') <p id="email-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="phone" class="text-sm font-medium">{{ __('Phone') }}</label>
                    <input id="phone" name="phone" type="tel" autocomplete="tel" dir="ltr" value="{{ old('phone') }}" class="{{ $field }}"
                           @error('phone') aria-invalid="true" aria-describedby="phone-error" @enderror>
                    @error('phone') <p id="phone-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="message" class="text-sm font-medium">{{ __('Message') }}</label>
                    <textarea id="message" name="message" rows="5" required maxlength="3000" class="{{ $field }}"
                              @error('message') aria-invalid="true" aria-describedby="message-error" @enderror>{{ old('message') }}</textarea>
                    @error('message') <p id="message-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="rounded-md bg-brand px-5 py-2.5 font-medium text-brand-contrast">{{ __('Send message') }}</button>
            </form>
        </section>
    </div>
</x-layouts::public>
