<?php

namespace Tests\Feature\Clinic;

use App\Actions\Scheduling\BookAppointment;
use App\Actions\Scheduling\SaveWeeklySchedule;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentStatusChange;
use App\Models\AuditLog;
use App\Models\BlockedPeriod;
use App\Models\Patient;
use App\Models\User;
use App\Support\Availability;
use App\Support\ClinicTime;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class AppointmentsTest extends TestCase
{
    use RefreshDatabase;

    /** A Wednesday. */
    private const DAY = '2026-10-14';

    private User $dentist;

    private User $reception;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        config(['clinic.timezone' => 'Asia/Amman']);
        // Monday 08:00 in Amman, two days before DAY.
        $this->travelTo(CarbonImmutable::parse('2026-10-12 05:00:00', 'UTC'));

        $this->dentist = User::factory()->dentist()->create(['name' => 'Dr. Slot']);
        $this->reception = User::factory()->receptionist()->withTwoFactor()->create();
        $this->patient = Patient::factory()->create(['first_name' => 'Lina', 'last_name' => 'Booked']);

        // Wednesday 09:00-13:00 with a 10:00-10:30 break.
        app(SaveWeeklySchedule::class)->handle($this->dentist, [3 => [['start' => '09:00', 'end' => '13:00']]], [3 => [['start' => '10:00', 'end' => '10:30']]]);

        $this->actingAs($this->reception);
    }

    public function test_free_slots_follow_hours_breaks_appointments_and_closures(): void
    {
        $this->assertSame(['09:00', '09:30', '10:30', '11:00', '11:30', '12:00', '12:30'], $this->slots(30, step: 30));

        $this->book('11:00', 30);
        BlockedPeriod::create(['dentist_id' => null, 'starts_at' => ClinicTime::toUtc(self::DAY, '12:30'), 'ends_at' => ClinicTime::toUtc(self::DAY, '13:00')]);

        $this->assertSame(['09:00', '09:30', '10:30', '11:30', '12:00'], $this->slots(30, step: 30));
        $this->assertSame([], Availability::freeSlots($this->dentist, '2026-10-16', 30), 'No hours on Friday.');
    }

    public function test_past_times_today_are_not_offered(): void
    {
        $this->travelTo(CarbonImmutable::parse(self::DAY.' 08:20:00', 'UTC')); // 11:20 in Amman

        $this->assertSame(['11:30', '12:00', '12:30'], $this->slots(30, step: 30));
    }

    public function test_booking_through_the_calendar_stores_utc_and_records_the_status(): void
    {
        Livewire::test('pages::clinic.appointments.index')
            ->set('date', self::DAY)
            ->call('openBooking', $this->dentist->id)
            ->set('patientSearch', 'Lina')
            ->assertSee('Lina Booked')
            ->call('pickPatient', $this->patient->id)
            ->set('form.time', '09:30')
            ->call('book')
            ->assertHasNoErrors()
            ->assertSee('09:30–10:00');

        $appointment = Appointment::sole();
        $this->assertSame('2026-10-14 06:30:00', $appointment->starts_at->utc()->format('Y-m-d H:i:s'));
        $this->assertSame(AppointmentStatus::Scheduled, $appointment->status);
        $this->assertSame('staff', $appointment->source);
        $this->assertSame(1, AppointmentStatusChange::where('appointment_id', $appointment->id)->count());
        $this->assertTrue(AuditLog::where('action', 'appointment.booked')->where('patient_id', $this->patient->id)->exists());
        // Seeing the dentist for an appointment puts them on the care team.
        $this->assertTrue($this->patient->hasCareTeamMember($this->dentist));
    }

    public function test_double_booking_is_refused(): void
    {
        $this->book('09:00', 60);

        $this->expectException(ValidationException::class);
        $this->book('09:30', 30);
    }

    public function test_cancelled_appointments_free_their_slot(): void
    {
        $first = $this->book('09:00', 30);
        $first->update(['status' => AppointmentStatus::Cancelled]);

        $this->assertSame('09:00', ClinicTime::local($this->book('09:00', 30)->starts_at)->format('H:i'));
    }

    public function test_closures_block_booking_and_outside_hours_needs_confirmation(): void
    {
        BlockedPeriod::create(['dentist_id' => $this->dentist->id, 'starts_at' => ClinicTime::toUtc(self::DAY, '12:00'), 'ends_at' => ClinicTime::toUtc(self::DAY, '13:00')]);

        Livewire::test('pages::clinic.appointments.index')
            ->call('openBooking', $this->dentist->id)
            ->call('pickPatient', $this->patient->id)
            ->set('form.date', self::DAY)
            ->set('form.time', '12:15')
            ->call('book')
            ->assertHasErrors(['form.time']);

        Livewire::test('pages::clinic.appointments.index')
            ->call('openBooking', $this->dentist->id)
            ->call('pickPatient', $this->patient->id)
            ->set('form.date', self::DAY)
            ->set('form.time', '15:00')
            ->call('book')
            ->assertHasErrors(['form.time'])
            ->set('form.outside_hours', true)
            ->call('book')
            ->assertHasNoErrors();

        $this->assertSame(1, Appointment::count());
    }

    public function test_an_appointment_can_be_moved_into_its_own_slot_range(): void
    {
        $appointment = $this->book('09:00', 60);

        Livewire::test('pages::clinic.appointments.index')
            ->set('date', self::DAY)
            ->call('openReschedule', $appointment->id)
            ->assertSet('form.time', '09:00')
            ->assertSet('form.duration', 60)
            ->set('form.time', '09:30')
            ->set('form.duration', 30)
            ->call('book')
            ->assertHasNoErrors();

        $this->assertSame('09:30', ClinicTime::local($appointment->fresh()->starts_at)->format('H:i'));
        $this->assertTrue(AuditLog::where('action', 'appointment.rescheduled')->exists());
    }

    public function test_status_flow_and_cancellation_reason(): void
    {
        $appointment = $this->book('09:00', 30);

        $component = Livewire::test('pages::clinic.appointments.index')
            ->set('date', self::DAY)
            ->call('setStatus', $appointment->id, 'confirmed')
            ->call('setStatus', $appointment->id, 'checked_in')
            ->call('setStatus', $appointment->id, 'completed');

        $this->assertSame(AppointmentStatus::Completed, $appointment->fresh()?->status);
        $this->assertSame(4, AppointmentStatusChange::where('appointment_id', $appointment->id)->count());
        $component->call('setStatus', $appointment->id, 'confirmed')->assertHasErrors(['status']);
        $this->assertSame(AppointmentStatus::Completed, $appointment->fresh()?->status);

        $other = $this->book('11:00', 30);
        Livewire::test('pages::clinic.appointments.index')
            ->set('date', self::DAY)
            ->call('startCancel', $other->id)
            ->call('confirmCancel')
            ->assertHasErrors(['cancelReason'])
            ->set('cancelReason', 'Patient is travelling')
            ->call('confirmCancel')
            ->assertHasNoErrors();

        $other->refresh();
        $this->assertSame(AppointmentStatus::Cancelled, $other->status);
        $this->assertSame('Patient is travelling', $other->cancellation_reason);
        $this->assertNotNull($other->cancelled_at);
    }

    public function test_dentists_see_only_their_own_calendar_and_cannot_book(): void
    {
        $colleague = User::factory()->dentist()->create(['name' => 'Dr. Colleague']);
        $mine = $this->book('09:00', 30);
        $theirs = Appointment::factory()->create(['dentist_id' => $colleague->id, 'starts_at' => ClinicTime::toUtc(self::DAY, '09:00'), 'ends_at' => ClinicTime::toUtc(self::DAY, '09:30')]);

        $this->actingAs($this->dentist->forceFill(['two_factor_confirmed_at' => now(), 'two_factor_secret' => encrypt('x')]));
        Livewire::test('pages::clinic.appointments.index')
            ->set('date', self::DAY)
            ->assertSee('Lina Booked')
            ->assertDontSee('Dr. Colleague')
            ->assertDontSee('New appointment')
            ->call('openBooking')
            ->assertForbidden();

        Livewire::test('pages::clinic.appointments.index')->call('setStatus', $mine->id, 'confirmed')->assertForbidden();
        Livewire::test('pages::clinic.appointments.index')->call('startCancel', $theirs->id)->assertNotFound();
    }

    public function test_patients_cannot_open_the_calendar(): void
    {
        $this->actingAs(User::factory()->patient()->create());

        $this->get(route('clinic.appointments.index'))->assertForbidden();
    }

    private function book(string $time, int $minutes): Appointment
    {
        $starts = ClinicTime::toUtc(self::DAY, $time);

        return app(BookAppointment::class)->handle($this->patient, $this->dentist, $starts, $starts->addMinutes($minutes), ['service_id' => null, 'notes' => null, 'source' => 'staff'], $this->reception);
    }

    /**
     * @return list<string>
     */
    private function slots(int $minutes, int $step): array
    {
        return array_values(array_filter(Availability::freeSlots($this->dentist, self::DAY, $minutes), fn (string $s) => ((int) substr($s, 3, 2)) % $step === 0));
    }
}
