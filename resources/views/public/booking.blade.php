@use('App\Support\ClinicDetails')
@use('App\Models\AppointmentRequest')

@php
    $phone = ClinicDetails::get('phone');
    $field = 'field';
    $chosenService = old('service', $selectedService);
    $chosenDentist = old('dentist', $selectedDentist);
@endphp

<x-layouts::public :title="__('Request an appointment')" :bleed="true" :description="__('Ask :clinic for an appointment online. We call you back to agree a time.', ['clinic' => ClinicDetails::name()])">
    <x-site.page-hero :title="__('Book an Appointment')" :eyebrow="__('Request an appointment')"
        :intro="__('Choose what suits you and we will call you back to agree a time that works.')" />

    <div class="site-container grid gap-10 py-10 md:py-16 lg:grid-cols-[2fr_1fr] lg:gap-14">
        <section aria-labelledby="booking-form-heading" class="rounded-[2rem] bg-white p-7 shadow-[0_30px_70px_-45px_rgb(34_27_29/0.45)] md:p-10">
            <h2 id="booking-form-heading" class="sr-only">{{ __('Appointment request form') }}</h2>
            <p class="text-ink-muted">{{ __('Tell us when suits you. This is a request, not a confirmed booking: the clinic will call you to agree a time.') }}</p>

            @if (session('status') === 'booking-requested')
                <div role="status" class="mt-4 rounded-2xl border border-status-planned bg-status-planned/5 p-4 text-sm" data-test="booking-requested">
                    {{ __('Thank you. Your request has been sent. The clinic will call you to confirm a time.') }}
                </div>
            @endif

            <form method="POST" action="{{ route('public.booking.store') }}" class="mt-8 space-y-5" novalidate>
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
                                    <input type="radio" name="preferred_time" value="{{ $time }}" class="size-4 accent-wine" @checked(old('preferred_time', 'any') === $time)>
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

                <button type="submit" class="btn btn-primary w-full sm:w-auto">{{ __('Send request') }}</button>
            </form>
        </section>

        <aside class="space-y-5 lg:sticky lg:top-28 lg:self-start">
            <div class="rounded-[2rem] bg-cream p-7">
                <h2 class="text-3xl">{{ __('What happens next?') }}</h2>
                <ol class="mt-5 space-y-4 text-sm leading-relaxed text-ink-muted">
                    <li class="flex gap-3"><span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-wine text-xs text-ivory">1</span>{{ __('We receive your request.') }}</li>
                    <li class="flex gap-3"><span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-wine text-xs text-ivory">2</span>{{ __('Our team calls you to agree a time.') }}</li>
                    <li class="flex gap-3"><span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-wine text-xs text-ivory">3</span>{{ __('Your appointment is confirmed.') }}</li>
                </ol>
            </div>
            <div class="rounded-[2rem] border border-line p-7 text-sm">
                <h2 class="text-2xl">{{ __('Prefer to call?') }}</h2>
                @if ($phone)
                    <p class="mt-3"><a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}" class="text-lg text-wine underline underline-offset-4" dir="ltr">{{ $phone }}</a></p>
                @endif
                <p class="mt-3 text-ink-muted">{{ __('For severe pain, swelling or bleeding, call the clinic. In an emergency, call emergency services.') }}</p>
            </div>
        </aside>
    </div>
</x-layouts::public>
