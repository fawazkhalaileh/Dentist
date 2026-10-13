<?php

namespace Tests\Feature\Clinic;

use App\Actions\Procedures\AddStarterProcedures;
use App\Actions\Scheduling\SaveWeeklySchedule;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentRequest;
use App\Models\Patient;
use App\Models\User;
use App\Support\ClinicDetails;
use App\Support\ClinicTime;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HomeDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $dentist;

    protected function setUp(): void
    {
        parent::setUp();

        config(['clinic.timezone' => 'Asia/Amman']);
        // Wednesday 14 October 2026, 08:00 in Amman.
        $this->travelTo(CarbonImmutable::parse('2026-10-14 05:00:00', 'UTC'));
        $this->dentist = User::factory()->dentist()->withTwoFactor()->create(['name' => 'Dr. Lina Haddad']);
    }

    public function test_every_staff_role_gets_a_home_page_and_patients_do_not(): void
    {
        foreach ([User::factory()->admin(), User::factory()->receptionist(), User::factory()->dentist()] as $factory) {
            $this->actingAs($factory->withTwoFactor()->create())->get(route('clinic.dashboard'))->assertOk()->assertSee('Good morning');
        }

        $this->actingAs(User::factory()->patient()->create())->get(route('clinic.dashboard'))->assertForbidden();
    }

    public function test_reception_sees_the_whole_clinic_day_and_checks_patients_in(): void
    {
        $colleague = User::factory()->dentist()->create(['name' => 'Dr. Colleague']);
        $mine = $this->appointment($this->dentist, '09:00', 'Sara');
        $this->appointment($colleague, '10:00', 'Omar');
        AppointmentRequest::create(['name' => 'Huda Request', 'phone' => '0795551234', 'preferred_time' => 'any', 'locale' => 'en']);
        $this->actingAs(User::factory()->receptionist()->withTwoFactor()->create());

        Livewire::test('pages::clinic.home')
            ->assertSee("Today's schedule")
            ->assertSee('Sara Patient')
            ->assertSee('Omar Patient')
            ->assertSee('Dr. Colleague')
            ->assertSee('Huda Request')
            ->call('setStatus', $mine->id, 'checked_in')
            ->assertHasNoErrors();

        $this->assertSame(AppointmentStatus::CheckedIn, $mine->fresh()?->status);
    }

    public function test_dentists_see_their_own_day_and_cannot_change_statuses(): void
    {
        $colleague = User::factory()->dentist()->create();
        $mine = $this->appointment($this->dentist, '09:00', 'Sara');
        $this->appointment($colleague, '10:00', 'Omar');
        $this->actingAs($this->dentist);

        Livewire::test('pages::clinic.home')
            ->assertSee('Good morning, Lina')
            ->assertSee('My day')
            ->assertSee('Sara Patient')
            ->assertDontSee('Omar Patient')
            ->assertDontSee('Requests to call back')
            ->assertDontSee('Get your clinic ready')
            ->call('setStatus', $mine->id, 'checked_in')
            ->assertForbidden();
    }

    public function test_the_week_strip_counts_appointments_per_clinic_day(): void
    {
        $this->appointment($this->dentist, '09:00', 'Sara');
        $this->appointment($this->dentist, '10:00', 'Omar', '2026-10-16');
        $this->actingAs(User::factory()->receptionist()->withTwoFactor()->create());

        $week = Livewire::test('pages::clinic.home')->instance()->week;

        $this->assertSame(['date' => '2026-10-14', 'count' => 1], $week[0]);
        $this->assertSame(['date' => '2026-10-16', 'count' => 1], $week[2]);
        $this->assertCount(7, $week);
    }

    public function test_the_setup_checklist_guides_admins_until_the_clinic_is_ready(): void
    {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());

        Livewire::test('pages::clinic.home')->assertSee('Get your clinic ready')->assertSee('1 of 5 steps done');

        ClinicDetails::put(['name' => ['en' => 'Smile Clinic', 'ar' => null]]);
        app(AddStarterProcedures::class)->handle();
        app(SaveWeeklySchedule::class)->handle($this->dentist, [0 => [['start' => '09:00', 'end' => '17:00']]], []);
        Patient::factory()->create();

        Livewire::test('pages::clinic.home')->assertDontSee('Get your clinic ready');
    }

    public function test_the_patient_search_respects_the_care_team(): void
    {
        Patient::factory()->assignedTo($this->dentist)->create(['first_name' => 'Mine', 'last_name' => 'Patient']);
        Patient::factory()->create(['first_name' => 'Other', 'last_name' => 'Patient']);
        $this->actingAs($this->dentist);

        Livewire::test('pages::clinic.home')
            ->set('patientSearch', 'Patient')
            ->assertSee('Mine Patient')
            ->assertDontSee('Other Patient');
    }

    private function appointment(User $dentist, string $time, string $firstName, string $date = '2026-10-14'): Appointment
    {
        $starts = ClinicTime::toUtc($date, $time);

        return Appointment::factory()->create([
            'dentist_id' => $dentist->id,
            'patient_id' => Patient::factory()->create(['first_name' => $firstName, 'last_name' => 'Patient'])->id,
            'starts_at' => $starts,
            'ends_at' => $starts->addMinutes(30),
        ]);
    }
}
