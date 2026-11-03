<?php

namespace Tests\Feature\Clinic;

use App\Actions\Staff\CreateSetupLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffSetupLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_staff_member_sets_a_password_with_the_link_a_day_later(): void
    {
        $dentist = User::factory()->dentist()->create(['email' => 'new.dentist@example.test']);
        $link = app(CreateSetupLink::class)->handle($dentist);

        $this->travel(23)->hours();

        $this->get($link)->assertOk()->assertSee('new.dentist@example.test');

        parse_str((string) parse_url($link, PHP_URL_QUERY), $query);
        $token = basename((string) parse_url($link, PHP_URL_PATH));
        $this->post(route('password.update'), [
            'token' => $token, 'email' => $query['email'],
            'password' => 'A-new-Password-1!', 'password_confirmation' => 'A-new-Password-1!',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('A-new-Password-1!', $dentist->fresh()->password));
    }

    public function test_opening_a_link_while_signed_in_explains_and_can_sign_out_and_continue(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Clinic Admin']);
        $link = app(CreateSetupLink::class)->handle(User::factory()->dentist()->create(['email' => 'dr@example.test']));

        $this->actingAs($admin)->get($link)
            ->assertRedirect(route('setup-link.signed-in', ['next' => $link]));

        $this->get(route('setup-link.signed-in', ['next' => $link]))
            ->assertOk()
            ->assertSee('Clinic Admin')
            ->assertSee('dr@example.test')
            ->assertSee('Sign out and continue');

        $this->post(route('setup-link.continue'), ['next' => $link])->assertRedirect($link);
        $this->assertGuest();
    }

    public function test_only_password_reset_links_on_this_site_are_followed(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('setup-link.signed-in', ['next' => 'https://evil.example/reset-password/x']))->assertNotFound();
        $this->post(route('setup-link.continue'), ['next' => '/clinic/patients'])->assertNotFound();
        $this->assertAuthenticated();
    }

    public function test_signed_in_users_still_go_to_the_dashboard_from_other_guest_pages(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('login'))->assertRedirect(route('dashboard'));
    }
}
