@use('App\Support\ClinicDetails')
@use('App\Models\AppointmentRequest')

@php
    $phone = ClinicDetails::get('phone');
    $field = 'mt-1 block w-full rounded-md border border-line bg-surface px-3 py-2';
    $chosenService = old('service', $selectedService);
    $chosenDentist = old('dentist', $selectedDentist);
@endphp

<x-layouts::public :title="__('Request an appointment')" :description="__('Ask :clinic for an appointment online. We call you back to agree a time.', ['clinic' => ClinicDetails::name()])">
    <x-ui.page-header :title="__('Request an appointment')" />

    <div class="grid gap-10 md:grid-cols-[2fr_1fr]">
        <section aria-labelledby="booking-form-heading">
            <h2 id="booking-form-heading" class="sr-only">{{ __('Appointment request form') }}</h2>
            <p class="text-ink-muted">{{ __('Tell us when suits you. This is a request, not a confirmed booking: the clinic will call you to agree a time.') }}</p>

            @if (session('status') === 'booking-requested')
                <div role="status" class="mt-4 rounded-md border border-status-planned p-4 text-sm" data-test="booking-requested">
                    {{ __('Thank you. Your request has been sent. The clinic will call you to confirm a time.') }}
                </div>
            @endif

            <form method="POST" action="{{ route('public.booking.store') }}" class="mt-6 space-y-4" novalidate>
                @csrf

                {{-- Honeypot: hidden from people and assistive technology --}}
                <div class="hidden" aria-hidden="true">
                    <label for="website">Website</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="name" class="text-sm font-medium">{{ __('Name') }}</label>
                        <input id="name" name="name" type="text" required autocomplete="name" value="{{ old('name') }}" class="{{ $field }}"
                               @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                        @error('name') <p id="name-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="phone" class="text-sm font-medium">{{ __('Phone') }}</label>
                        <input id="phone" name="phone" type="tel" required autocomplete="tel" dir="ltr" value="{{ old('phone') }}" class="{{ $field }}"
                               @error('phone') aria-invalid="true" aria-describedby="phone-error" @enderror>
                        @error('phone') <p id="phone-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="email" class="text-sm font-medium">{{ __('Email') }} <span class="font-normal text-ink-muted">({{ __('optional') }})</span></label>
                    <input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" class="{{ $field }}"
                           @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                    @error('email') <p id="email-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    @if ($services->isNotEmpty())
                        <div>
                            <label for="service" class="text-sm font-medium">{{ __('Treatment') }}</label>
                            <select id="service" name="service" class="{{ $field }}">
                                <option value="">{{ __('Not sure / check-up') }}</option>
                                @foreach ($services as $service)
                                    <option value="{{ $service->id }}" @selected((string) $chosenService === (string) $service->id)>{{ $service->translation()?->title }}</option>
                                @endforeach
                            </select>
                            @error('service') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                        </div>
                    @endif
                    @if ($dentists->isNotEmpty())
                        <div>
                            <label for="dentist" class="text-sm font-medium">{{ __('Preferred dentist') }}</label>
                            <select id="dentist" name="dentist" class="{{ $field }}">
                                <option value="">{{ __('No preference') }}</option>
                                @foreach ($dentists as $profile)
                                    <option value="{{ $profile->user_id }}" @selected((string) $chosenDentist === (string) $profile->user_id)>{{ $profile->translation()?->display_name }}</option>
                                @endforeach
                            </select>
                            @error('dentist') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                        </div>
                    @endif
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="preferred_date" class="text-sm font-medium">{{ __('Preferred date') }} <span class="font-normal text-ink-muted">({{ __('optional') }})</span></label>
                        <input id="preferred_date" name="preferred_date" type="date" min="{{ \App\Support\ClinicTime::today() }}" value="{{ old('preferred_date') }}" class="{{ $field }}"
                               @error('preferred_date') aria-invalid="true" aria-describedby="preferred-date-error" @enderror>
                        @error('preferred_date') <p id="preferred-date-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <fieldset>
                        <legend class="text-sm font-medium">{{ __('Preferred time') }}</legend>
                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-2">
                            @foreach (AppointmentRequest::PREFERRED_TIMES as $time)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="radio" name="preferred_time" value="{{ $time }}" @checked(old('preferred_time', 'any') === $time)>
                                    {{ AppointmentRequest::preferredTimeLabel($time) }}
                                </label>
                            @endforeach
                        </div>
                        @error('preferred_time') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </fieldset>
                </div>

                <div>
                    <label for="message" class="text-sm font-medium">{{ __('Anything we should know?') }} <span class="font-normal text-ink-muted">({{ __('optional') }})</span></label>
                    <textarea id="message" name="message" rows="4" maxlength="2000" class="{{ $field }}"
                              @error('message') aria-invalid="true" aria-describedby="message-error" @enderror>{{ old('message') }}</textarea>
                    @error('message') <p id="message-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <p class="text-xs text-ink-muted">{{ __('We use these details only to arrange your appointment.') }} <a href="{{ route('public.legal.privacy') }}" class="underline">{{ __('Privacy policy') }}</a></p>

                <button type="submit" class="rounded-md bg-brand px-5 py-2.5 font-medium text-brand-contrast">{{ __('Send request') }}</button>
            </form>
        </section>

        <aside class="space-y-3 text-sm">
            <h2 class="font-semibold">{{ __('Prefer to call?') }}</h2>
            @if ($phone)
                <p><a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" class="text-lg underline" dir="ltr">{{ $phone }}</a></p>
            @endif
            <p class="text-ink-muted">{{ __('For severe pain, swelling or bleeding, call the clinic. In an emergency, call emergency services.') }}</p>
        </aside>
    </div>
</x-layouts::public>
