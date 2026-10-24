<?php

namespace Tests\Feature\Clinic;

use App\Actions\Scheduling\BookAppointment;
use App\Actions\Scheduling\ChangeAppointmentStatus;
use App\Actions\Scheduling\SaveWeeklySchedule;
use App\Enums\AppointmentRequestStatus;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentRequest;
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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AppointmentEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    private User $dentist;

    private User $reception;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        config(['clinic.timezone' => 'Asia/Amman']);
        $this->travelTo(CarbonImmutable::parse('2026-10-12 05:00:00', 'UTC'));
        $this->dentist = User::factory()->dentist()->withTwoFactor()->create();
        $this->reception = User::factory()->receptionist()->withTwoFactor()->create();
        $this->patient = Patient::factory()->create();
        app(SaveWeeklySchedule::class)->handle($this->dentist, [3 => [['start' => '09:00', 'end' => '13:00']]], []);
        $this->actingAs($this->reception);
    }

    /** @return array<string, array{string, int}> */
    public static function overlaps(): array
    {
        return ['identical' => ['10:00', 60], 'starts before' => ['09:45', 30], 'ends after' => ['10:45', 30], 'contains existing' => ['09:45', 90]];
    }

    #[DataProvider('overlaps')]
    public function test_overlap_is_rejected_even_with_the_hours_override(string $time, int $minutes): void
    {
        $existing = $this->book('10:00', 60);
        $auditCount = AuditLog::count();

        try {
            $this->book($time, $minutes, override: true);
            $this->fail('An overlapping booking was accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('time', $e->errors());
        }

        $this->assertSame(1, Appointment::count());
        $this->assertSame(1, $existing->statusChanges()->count());
        $this->assertSame($auditCount, AuditLog::count());
    }

    public function test_touching_slots_and_a_different_dentist_do_not_conflict(): void
    {
        $this->book('10:00', 60);
        $this->book('09:30', 30);
        $this->book('11:00', 30);
        $other = User::factory()->dentist()->create();
        $start = ClinicTime::toUtc('2026-10-14', '10:00');
        app(BookAppointment::class)->handle($this->patient, $other, $start, $start->addHour(), ['service_id' => null, 'notes' => null, 'source' => 'staff'], $this->reception, true);

        $this->assertSame(4, Appointment::count());
    }

    public function test_a_day_off_requires_an_explicit_override(): void
    {
        try {
            $this->book('10:00', 30, date: '2026-10-16');
            $this->fail('A day off was booked without confirmation.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('time', $e->errors());
        }

        $this->assertSame(0, Appointment::count());
        $appointment = $this->book('10:00', 30, override: true, date: '2026-10-16');
        $this->assertSame('2026-10-16 10:00', ClinicTime::local($appointment->starts_at)->format('Y-m-d H:i'));
    }

    public function test_the_hours_override_cannot_bypass_a_clinic_closure(): void
    {
        BlockedPeriod::create(['dentist_id' => null, 'starts_at' => ClinicTime::toUtc('2026-10-14', '10:00'), 'ends_at' => ClinicTime::toUtc('2026-10-14', '11:00')]);

        $this->expectException(ValidationException::class);
        $this->book('10:30', 30, override: true);
    }

    /** @return array<string, array{AppointmentStatus, AppointmentStatus, bool}> */
    public static function transitions(): array
    {
        return [
            'direct check in' => [AppointmentStatus::Scheduled, AppointmentStatus::CheckedIn, true],
            'scheduled no show' => [AppointmentStatus::Scheduled, AppointmentStatus::NoShow, true],
            'confirmed no show' => [AppointmentStatus::Confirmed, AppointmentStatus::NoShow, true],
            'finish checked in' => [AppointmentStatus::CheckedIn, AppointmentStatus::Completed, true],
            'cannot skip check in' => [AppointmentStatus::Scheduled, AppointmentStatus::Completed, false],
            'checked in cannot no show' => [AppointmentStatus::CheckedIn, AppointmentStatus::NoShow, false],
            'checked in cannot cancel' => [AppointmentStatus::CheckedIn, AppointmentStatus::Cancelled, false],
            'completed is final' => [AppointmentStatus::Completed, AppointmentStatus::CheckedIn, false],
            'cancelled is final' => [AppointmentStatus::Cancelled, AppointmentStatus::Scheduled, false],
            'no show is final' => [AppointmentStatus::NoShow, AppointmentStatus::Confirmed, false],
            'repeat status' => [AppointmentStatus::Scheduled, AppointmentStatus::Scheduled, false],
        ];
    }

    #[DataProvider('transitions')]
    public function test_status_changes_preserve_history_and_refuse_invalid_transitions(AppointmentStatus $from, AppointmentStatus $to, bool $allowed): void
    {
        $appointment = Appointment::factory()->create(['status' => $from]);
        $auditCount = AuditLog::count();

        try {
            app(ChangeAppointmentStatus::class)->handle($appointment, $to, $this->reception, 'Patient called');
            $this->assertTrue($allowed, 'An invalid transition was accepted.');
        } catch (ValidationException $e) {
            $this->assertFalse($allowed, 'A valid transition was refused.');
            $this->assertArrayHasKey('status', $e->errors());
        }

        $this->assertSame($allowed ? $to : $from, $appointment->fresh()->status);
        $this->assertSame($allowed ? 1 : 0, $appointment->statusChanges()->count());
        $this->assertSame($auditCount + ($allowed ? 1 : 0), AuditLog::count());
        if ($allowed) {
            $change = $appointment->statusChanges()->sole();
            $this->assertSame($from, $change->from_status);
            $this->assertSame($to, $change->to_status);
            $this->assertSame($this->reception->id, $change->changed_by);
        }
    }

    public function test_a_stale_model_cannot_reopen_a_cancelled_appointment(): void
    {
        $appointment = $this->book('10:00', 30);
        $change = app(ChangeAppointmentStatus::class);
        $change->handle($appointment, AppointmentStatus::Cancelled, $this->reception, 'Patient called');

        try {
            $change->handle($appointment, AppointmentStatus::CheckedIn, $this->reception);
            $this->fail('The stale scheduled model reopened a cancelled appointment.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }

        $this->assertSame(AppointmentStatus::Cancelled, $appointment->fresh()->status);
        $this->assertSame(2, $appointment->statusChanges()->count());
        $this->assertContains('10:00', Availability::freeSlots($this->dentist, '2026-10-14', 30));
        $replacement = $this->book('10:00', 30);
        $this->assertNotSame($appointment->id, $replacement->id);
    }

    /** @return array<string, array{string, string, string}> */
    public static function localTimes(): array
    {
        return [
            'midnight rolls back UTC date' => ['2026-10-14', '00:15', '2026-10-13 21:15'],
            'historical winter offset' => ['2021-01-15', '09:00', '2021-01-15 07:00'],
            'before spring DST jump' => ['2021-03-25', '23:30', '2021-03-25 21:30'],
            'after spring DST jump' => ['2021-03-26', '01:30', '2021-03-25 22:30'],
            'permanent summer time in winter' => ['2026-12-15', '09:00', '2026-12-15 06:00'],
        ];
    }

    #[DataProvider('localTimes')]
    public function test_clinic_time_round_trips_midnight_and_historical_offsets(string $date, string $time, string $utc): void
    {
        $instant = ClinicTime::toUtc($date, $time);
        $this->assertSame(0, $instant->utcOffset());
        $this->assertSame($utc, $instant->format('Y-m-d H:i'));
        $this->assertSame($date.' '.$time, ClinicTime::local($instant)->format('Y-m-d H:i'));
    }

    public function test_the_calendar_and_home_use_the_local_day_at_utc_date_boundaries(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-13 21:05:00', 'UTC'));
        $early = $this->book('00:15', 30, override: true);
        $late = $this->book('23:30', 15, override: true);
        $this->book('00:00', 30, override: true, date: '2026-10-15');
        $this->book('23:30', 15, override: true, date: '2026-10-13');

        $this->assertSame('2026-10-14', ClinicTime::today());
        $this->assertSame(3, ClinicTime::weekday(ClinicTime::today()));
        $calendar = Livewire::test('pages::clinic.appointments.index')->assertSet('date', '2026-10-14');
        $this->assertSame([$early->id, $late->id], $calendar->instance()->appointments->modelKeys());
        $home = Livewire::test('pages::clinic.home');
        $this->assertSame([$early->id, $late->id], $home->instance()->today->modelKeys());
    }

    public function test_a_conflicting_request_stays_pending_then_can_be_handled_once(): void
    {
        $this->book('10:00', 30);
        $request = AppointmentRequest::create([
            'name' => 'Request Retry', 'phone' => '0777000222', 'locale' => 'en',
            'preferred_dentist_id' => $this->dentist->id, 'preferred_date' => '2026-10-14',
            'preferred_time' => 'morning',
        ]);
        $component = Livewire::test('pages::clinic.appointment-requests.index')
            ->call('startBooking', $request->id)
            ->set('form.time', '10:00')
            ->call('book')
            ->assertHasErrors(['form.time']);

        $this->assertSame(AppointmentRequestStatus::Pending, $request->fresh()->status);
        $this->assertNull($request->fresh()->handled_at);
        $this->assertNull($request->fresh()->appointment_id);
        $this->assertSame(1, Patient::count());
        $this->assertSame(1, Appointment::count());

        $component->set('form.time', '11:00')->call('book')->assertHasNoErrors();
        $request->refresh();
        $this->assertSame(AppointmentRequestStatus::Approved, $request->status);
        $this->assertSame($this->reception->id, $request->handled_by);
        $this->assertNotNull($request->handled_at);
        $this->assertSame('public_request', $request->appointment->source);
        $this->assertSame($request->patient_id, $request->appointment->patient_id);
        $this->assertSame(2, Patient::count());
        $this->assertSame(2, Appointment::count());

        Livewire::test('pages::clinic.appointment-requests.index')
            ->call('startBooking', $request->id)->assertNotFound();
        $this->assertSame(2, Appointment::count());
    }

    private function book(string $time, int $minutes, bool $override = false, string $date = '2026-10-14'): Appointment
    {
        $start = ClinicTime::toUtc($date, $time);

        return app(BookAppointment::class)->handle($this->patient, $this->dentist, $start, $start->addMinutes($minutes), ['service_id' => null, 'notes' => null, 'source' => 'staff'], $this->reception, $override);
    }
}
