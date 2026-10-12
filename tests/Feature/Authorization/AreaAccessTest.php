<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AreaAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_login_from_protected_areas(): void
    {
        $this->get(route('clinic.dashboard'))->assertRedirect(route('login'));
        $this->get(route('portal.dashboard'))->assertRedirect(route('login'));
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_staff_land_in_the_clinic_and_patients_in_the_portal(): void
    {
        $dentist = User::factory()->dentist()->withTwoFactor()->create();
        $patient = User::factory()->patient()->create();

        $this->actingAs($dentist)->get(route('dashboard'))->assertRedirect(route('clinic.dashboard'));
        $this->actingAs($patient)->get(route('dashboard'))->assertRedirect(route('portal.dashboard'));
    }

    public function test_a_user_without_a_role_is_refused(): void
    {
        $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertForbidden();
    }

    public function test_patients_cannot_open_the_clinic_area(): void
    {
        $patient = User::factory()->patient()->create();

        $this->actingAs($patient)->get(route('clinic.dashboard'))->assertForbidden();
        $this->actingAs($patient)->get(route('clinic.patients.index'))->assertForbidden();
    }

    public function test_staff_cannot_open_the_patient_portal(): void
    {
        $reception = User::factory()->receptionist()->withTwoFactor()->create();

        $this->actingAs($reception)->get(route('portal.dashboard'))->assertForbidden();
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function moduleAccessMatrix(): array
    {
        return [
            'admin: audit log' => ['admin', 'clinic.audit.index', true],
            'admin: staff' => ['admin', 'clinic.staff.index', true],
            'admin: settings' => ['admin', 'clinic.settings.index', true],
            'admin: procedures' => ['admin', 'clinic.procedures.index', true],

            'dentist: patients list' => ['dentist', 'clinic.patients.index', true],
            'dentist: own calendar' => ['dentist', 'clinic.appointments.index', true],
            'dentist: no new patients' => ['dentist', 'clinic.patients.create', false],
            'dentist: no requests' => ['dentist', 'clinic.appointment-requests.index', false],
            'dentist: no website content' => ['dentist', 'clinic.content.services.index', false],
            'dentist: no staff admin' => ['dentist', 'clinic.staff.index', false],
            'dentist: no audit log' => ['dentist', 'clinic.audit.index', false],

            'receptionist: patients list' => ['receptionist', 'clinic.patients.index', true],
            'receptionist: new patient' => ['receptionist', 'clinic.patients.create', true],
            'receptionist: calendar' => ['receptionist', 'clinic.appointments.index', true],
            'receptionist: requests' => ['receptionist', 'clinic.appointment-requests.index', true],
            'receptionist: schedules' => ['receptionist', 'clinic.schedules.index', true],
            'receptionist: website content' => ['receptionist', 'clinic.content.blog.index', true],
            'receptionist: no procedures' => ['receptionist', 'clinic.procedures.index', false],
            'receptionist: no settings' => ['receptionist', 'clinic.settings.index', false],
            'receptionist: no audit log' => ['receptionist', 'clinic.audit.index', false],
        ];
    }

    #[DataProvider('moduleAccessMatrix')]
    public function test_clinic_modules_are_restricted_by_role(string $role, string $route, bool $allowed): void
    {
        $user = User::factory()->{$role}()->withTwoFactor()->create();

        $response = $this->actingAs($user)->get(route($route));

        $allowed ? $response->assertOk() : $response->assertForbidden();
    }

    public function test_navigation_only_lists_modules_the_user_can_open(): void
    {
        $dentist = User::factory()->dentist()->withTwoFactor()->create();

        $this->actingAs($dentist)->get(route('clinic.dashboard'))
            ->assertOk()
            ->assertSee(route('clinic.patients.index'))
            ->assertDontSee(route('clinic.audit.index'))
            ->assertDontSee(route('clinic.staff.index'));
    }

    public function test_deactivated_users_are_signed_out(): void
    {
        $user = User::factory()->receptionist()->withTwoFactor()->inactive()->create();

        $this->actingAs($user)->get(route('clinic.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
