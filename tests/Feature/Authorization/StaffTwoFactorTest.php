<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StaffTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function staffRoles(): array
    {
        return [['admin'], ['dentist'], ['receptionist']];
    }

    #[DataProvider('staffRoles')]
    public function test_staff_without_two_factor_are_sent_to_security_settings(string $role): void
    {
        $user = User::factory()->{$role}()->create();

        $this->actingAs($user)
            ->get(route('clinic.dashboard'))
            ->assertRedirect(route('security.edit'));
    }

    public function test_security_page_explains_the_requirement_until_two_factor_is_confirmed(): void
    {
        $withoutTwoFactor = User::factory()->dentist()->create();
        $withTwoFactor = User::factory()->dentist()->withTwoFactor()->create();
        $confirmed = ['auth.password_confirmed_at' => time()];

        $this->actingAs($withoutTwoFactor)->withSession($confirmed)
            ->get(route('security.edit'))
            ->assertOk()
            ->assertSee('data-test="two-factor-required"', escape: false);

        $this->actingAs($withTwoFactor)->withSession($confirmed)
            ->get(route('security.edit'))
            ->assertOk()
            ->assertDontSee('data-test="two-factor-required"', escape: false);
    }

    public function test_unconfirmed_two_factor_does_not_count(): void
    {
        $user = User::factory()->dentist()->create([
            'two_factor_secret' => encrypt('secret'),
            'two_factor_confirmed_at' => null,
        ]);

        $this->actingAs($user)->get(route('clinic.dashboard'))->assertRedirect(route('security.edit'));
    }

    public function test_staff_with_confirmed_two_factor_can_use_the_clinic(): void
    {
        $user = User::factory()->dentist()->withTwoFactor()->create();

        $this->actingAs($user)->get(route('clinic.dashboard'))->assertOk();
    }

    public function test_requirement_applies_to_every_clinic_route_not_just_the_dashboard(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)->get(route('clinic.audit.index'))->assertRedirect(route('security.edit'));
    }

    public function test_patients_are_not_required_to_use_two_factor(): void
    {
        $patient = User::factory()->patient()->create();

        $this->actingAs($patient)->get(route('portal.dashboard'))->assertOk();
    }

    public function test_requirement_can_be_disabled_by_configuration(): void
    {
        config(['clinic.security.require_staff_mfa' => false]);
        $user = User::factory()->dentist()->create();

        $this->actingAs($user)->get(route('clinic.dashboard'))->assertOk();
    }
}
