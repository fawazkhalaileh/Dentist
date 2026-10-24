<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

/**
 * With two-factor sign-in switched off (CLINIC_TWO_FACTOR=false), the password is enough.
 */
class PasswordOnlySignInTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // What config/fortify.php and config/clinic.php produce when CLINIC_TWO_FACTOR=false.
        config([
            'fortify.features' => array_values(array_filter(
                config('fortify.features'),
                fn ($feature) => $feature !== Features::twoFactorAuthentication(),
            )),
            'clinic.security.require_staff_mfa' => false,
        ]);
    }

    public function test_staff_with_an_authenticator_set_up_sign_in_with_the_password_only(): void
    {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($admin);
        $this->get(route('clinic.dashboard'))->assertOk();
    }

    public function test_staff_without_two_factor_can_use_the_clinic(): void
    {
        $receptionist = User::factory()->receptionist()->create();

        $this->actingAs($receptionist)->get(route('clinic.dashboard'))->assertOk();
    }

    public function test_the_security_page_hides_two_factor_settings(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('security.edit'))
            ->assertOk()
            ->assertDontSee('Enable two-factor authentication');
    }

    public function test_help_describes_password_only_setup_when_two_factor_is_disabled(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('clinic.help'))
            ->assertOk()
            ->assertSee('They choose a password.')
            ->assertSee('change your password and language.')
            ->assertDontSee('two-factor')
            ->assertDontSee('authenticator');
    }
}
