<?php

use App\Enums\Permission;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Short how-to guides for everyday tasks. Each guide is shown only to people
 * who can do that task, so the page matches what the user sees in the menu.
 */
new #[Title('Help')] class extends Component {
    /**
     * @return list<array{icon: string, title: string, steps: list<string>}>
     */
    public function guides(): array
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $guides = [];

        if ($user->can(Permission::StaffManage->value)) {
            $guides[] = ['icon' => 'sparkles', 'title' => __('Setting up the clinic (administrators)'), 'steps' => [
                __('Website → Clinic details: enter the clinic name in English and Arabic, phone, address and opening hours.'),
                __('Procedures: press “Add starter procedures”, then adjust names, durations and chart colours.'),
                __('Dentists and staff: add each person, then press “Create setup link” and send it to them. They choose a password and set up two-factor authentication.'),
                __('Working hours: choose a dentist, set their weekly hours and breaks, and add closures such as public holidays.'),
            ]];
        }

        if ($user->can('create', Patient::class)) {
            $guides[] = ['icon' => 'user-plus', 'title' => __('Registering a patient'), 'steps' => [
                __('Patients → New patient. Name and a phone number (or email) are enough to start.'),
                __('If the patient may already exist, the system shows possible matches first. Open the match instead of creating a duplicate.'),
                __('Tick the dentists who look after the patient under “Care team”.'),
            ]];
        }

        if ($user->can('create', Appointment::class)) {
            $guides[] = ['icon' => 'calendar-days', 'title' => __('Booking an appointment'), 'steps' => [
                __('Calendar: click an empty time in the dentist’s column. Grey times are outside working hours.'),
                __('Search for the patient, check the duration, and press “Book appointment”.'),
                __('From a patient’s page, “Book appointment” opens the calendar with the patient already chosen.'),
                __('On the day: Home shows today’s list. Press “Check in” when the patient arrives and “Done” when they leave.'),
                __('Click an appointment in the calendar to confirm, move or cancel it. Cancelling asks for a reason.'),
            ]];
        }

        if ($user->can(Permission::AppointmentRequestsManage->value)) {
            $guides[] = ['icon' => 'inbox-arrow-down', 'title' => __('Requests from the website'), 'steps' => [
                __('Requests (with a number next to it in the menu) lists people who asked for an appointment online.'),
                __('Call them, agree a time, then press “Book appointment”: pick the existing patient (found by phone) or register a new one.'),
                __('If they no longer need an appointment, press “Decline”.'),
            ]];
        }

        if ($user->can('viewAny', Patient::class)) {
            $guides[] = ['icon' => 'users', 'title' => __('Working with a patient record'), 'steps' => [
                __('Open a patient from Patients or the search on Home. Everything about them is on one page with tabs.'),
                __('Overview shows what was done, what is planned, the last and next visit, and the dental chart.'),
                __('Allergies appear at the top of every tab for clinical staff. Record them under Medical history; tick “No known allergies” when the patient has none.'),
            ]];
        }

        if ($user->can(Permission::ClinicalWrite->value)) {
            $guides[] = ['icon' => 'squares-2x2', 'title' => __('Charting and treatments (dentists)'), 'steps' => [
                __('Open the patient, then “Chart & treatments”. Click a tooth to see its treatments.'),
                __('Press “Add treatment”: choose the procedure, the tooth and, for fillings, the surfaces. The chart colours the tooth in the procedure’s colour.'),
                __('Light dashed colour = planned, medium = in progress, solid = completed. Use Start, Complete or Cancel on each treatment.'),
                __('Write a clinical note under Notes after each visit. Notes cannot be edited; use “Correct” to fix a mistake, and the original is kept.'),
            ]];
        }

        $guides[] = ['icon' => 'shield-check', 'title' => __('Your account'), 'steps' => [
            __('Your name at the bottom of the menu → Settings: change your password, language and two-factor authentication.'),
            __('Lost your phone with the authenticator? Ask an administrator to reset your two-factor authentication.'),
            __('Every opening of a patient record is logged, so only open records you need.'),
        ]];

        return $guides;
    }
}; ?>

<section class="w-full max-w-4xl">
    <x-ui.page-header :title="__('Help')" :description="__('Short guides for the things you do every day. Only tasks you can do are shown.')" />

    <div class="space-y-4">
        @foreach ($this->guides() as $i => $guide)
            <details class="group rounded-xl border border-zinc-200 dark:border-zinc-700" @if ($i === 0) open @endif>
                <summary class="flex cursor-pointer list-none items-center gap-3 px-5 py-4">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-soft text-brand"><flux:icon :icon="$guide['icon']" class="size-5" /></span>
                    <span class="flex-1 font-semibold">{{ $guide['title'] }}</span>
                    <flux:icon.chevron-down class="size-4 text-zinc-400 transition group-open:rotate-180" />
                </summary>
                <ol class="space-y-2 px-5 pb-5 ps-[4.25rem]">
                    @foreach ($guide['steps'] as $n => $step)
                        <li class="flex gap-3 text-sm">
                            <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-xs font-semibold dark:bg-zinc-800">{{ $n + 1 }}</span>
                            <span class="pt-0.5">{{ $step }}</span>
                        </li>
                    @endforeach
                </ol>
            </details>
        @endforeach
    </div>
</section>
