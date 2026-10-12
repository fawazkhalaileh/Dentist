<?php

namespace Tests\Feature\Clinic;

use App\Actions\Scheduling\SaveWeeklySchedule;
use App\Enums\AppointmentRequestStatus;
use App\Models\Appointment;
use App\Models\AppointmentRequest;
use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\StaffProfile;
use App\Models\User;
use App\Support\ClinicTime;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Concerns\CreatesContent;
use Tests\TestCase;

class BookingRequestsTest extends TestCase
{
    use CreatesContent, RefreshDatabase;

    private User $dentist;

    protected function setUp(): void
    {
        parent::setUp();

        config(['clinic.timezone' => 'Asia/Amman']);
        $this->travelTo(CarbonImmutable::parse('2026-10-12 05:00:00', 'UTC'));
        $this->dentist = User::factory()->dentist()->create(['name' => 'Dr. Request']);
        $profile = StaffProfile::factory()->create(['user_id' => $this->dentist->id, 'slug' => 'dr-request', 'is_public' => true]);
        $profile->translations()->create(['locale' => 'en', 'display_name' => 'Dr. Request']);
        app(SaveWeeklySchedule::class)->handle($this->dentist, [3 => [['start' => '09:00', 'end' => '13:00']]], []);
    }

    public function test_a_visitor_sends_a_request_with_an_encrypted_message(): void
    {
        $service = $this->makeService(['en' => 'Cleaning']);

        $this->get(route('public.booking', ['locale' => 'en', 'service' => $service->id]))
            ->assertOk()
            ->assertSee('Dr. Request')
            ->assertSee('<option value="'.$service->id.'" selected', escape: false);

        $this->post(route('public.booking.store', ['locale' => 'ar']), [
            'name' => 'Sara Nasser',
            'phone' => '079 123 4567',
            'dentist' => $this->dentist->id,
            'service' => $service->id,
            'preferred_date' => '2026-10-14',
            'preferred_time' => 'morning',
            'message' => 'Tooth pain on the left',
        ])->assertRedirect(route('public.booking', ['locale' => 'ar']));

        $request = AppointmentRequest::sole();
        $this->assertSame(AppointmentRequestStatus::Pending, $request->status);
        $this->assertSame('ar', $request->locale);
        $this->assertSame('Tooth pain on the left', $request->message);
        $this->assertStringNotContainsString('Tooth pain', (string) DB::table('appointment_requests')->value('message'));
    }

    public function test_requests_are_validated_and_bots_rejected(): void
    {
        $this->post(route('public.booking.store', ['locale' => 'en']), ['name' => '', 'phone' => 'abc', 'preferred_time' => 'midnight', 'preferred_date' => '2020-01-01'])
            ->assertSessionHasErrors(['name', 'phone', 'preferred_time', 'preferred_date']);

        // Hidden or non-dentist staff cannot be chosen.
        $receptionist = User::factory()->receptionist()->create();
        $this->post(route('public.booking.store', ['locale' => 'en']), ['name' => 'X', 'phone' => '0791234567', 'preferred_time' => 'any', 'dentist' => $receptionist->id])
            ->assertSessionHasErrors(['dentist']);

        $this->post(route('public.booking.store', ['locale' => 'en']), ['name' => 'Bot', 'phone' => '0791234567', 'preferred_time' => 'any', 'website' => 'http://spam'])
            ->assertSessionHasErrors(['website']);

        $this->assertSame(0, AppointmentRequest::count());
    }

    public function test_reception_books_a_request_for_an_existing_patient_found_by_phone(): void
    {
        $existing = Patient::factory()->create(['first_name' => 'Sara', 'last_name' => 'Nasser', 'phone' => '+962 79 123 4567']);
        $request = $this->makeRequest(['phone' => '0791234567']);
        $this->actingAs(User::factory()->receptionist()->withTwoFactor()->create());

        Livewire::test('pages::clinic.appointment-requests.index')
            ->assertSee('Sara Nasser')
            ->call('startBooking', $request->id)
            ->assertSet('patientChoice', $existing->id)
            ->assertSet('form.date', '2026-10-14')
            ->assertSet('form.dentist_id', $this->dentist->id)
            ->set('form.time', '09:30')
            ->call('book')
            ->assertHasNoErrors();

        $appointment = Appointment::sole();
        $this->assertSame($existing->id, $appointment->patient_id);
        $this->assertSame('public_request', $appointment->source);
        $this->assertSame('09:30', ClinicTime::local($appointment->starts_at)->format('H:i'));
        $request->refresh();
        $this->assertSame(AppointmentRequestStatus::Approved, $request->status);
        $this->assertSame($appointment->id, $request->appointment_id);
        $this->assertTrue(AuditLog::where('action', 'appointment_request.approved')->exists());
        $this->assertSame(1, Patient::count());
    }

    public function test_reception_can_register_a_new_patient_from_a_request(): void
    {
        $request = $this->makeRequest(['name' => 'Omar Al Khalil', 'phone' => '0777000111', 'email' => 'omar@example.test']);
        $this->actingAs(User::factory()->receptionist()->withTwoFactor()->create());

        Livewire::test('pages::clinic.appointment-requests.index')
            ->call('startBooking', $request->id)
            ->assertSet('patientChoice', 0)
            ->assertSet('form.first_name', 'Omar')
            ->assertSet('form.last_name', 'Al Khalil')
            ->set('form.time', '10:00')
            ->call('book')
            ->assertHasNoErrors();

        $patient = Patient::sole();
        $this->assertSame('0777000111', $patient->phone);
        $this->assertSame('omar@example.test', $patient->email);
        $this->assertSame($patient->id, Appointment::sole()->patient_id);
    }

    public function test_a_failed_booking_leaves_no_new_patient_behind(): void
    {
        $request = $this->makeRequest(['phone' => '0777000222']);
        $this->actingAs(User::factory()->receptionist()->withTwoFactor()->create());

        Livewire::test('pages::clinic.appointment-requests.index')
            ->call('startBooking', $request->id)
            ->set('form.time', '18:00') // outside working hours, not confirmed
            ->call('book')
            ->assertHasErrors(['form.time']);

        $this->assertSame(0, Patient::count());
        $this->assertSame(AppointmentRequestStatus::Pending, $request->fresh()?->status);
    }

    public function test_requests_can_be_declined_and_only_staff_with_permission_see_them(): void
    {
        $request = $this->makeRequest();
        $this->actingAs(User::factory()->receptionist()->withTwoFactor()->create());

        Livewire::test('pages::clinic.appointment-requests.index')
            ->call('decline', $request->id)
            ->assertDontSee($request->name);

        $this->assertSame(AppointmentRequestStatus::Declined, $request->fresh()?->status);

        $this->actingAs(User::factory()->dentist()->withTwoFactor()->create());
        $this->get(route('clinic.appointment-requests.index'))->assertForbidden();
    }

    public function test_closing_the_booking_form_works(): void
    {
        $request = $this->makeRequest();
        $this->actingAs(User::factory()->receptionist()->withTwoFactor()->create());

        Livewire::test('pages::clinic.appointment-requests.index')
            ->call('startBooking', $request->id)
            ->call('closeBooking')
            ->assertSet('bookingId', null);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeRequest(array $overrides = []): AppointmentRequest
    {
        return AppointmentRequest::create([
            'name' => 'Sara Nasser',
            'phone' => '0791234567',
            'preferred_dentist_id' => $this->dentist->id,
            'preferred_date' => '2026-10-14',
            'preferred_time' => 'morning',
            'locale' => 'en',
            ...$overrides,
        ]);
    }
}
