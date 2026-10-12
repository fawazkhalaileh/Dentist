<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Admin-Passw0rd!';

    public function test_it_creates_a_verified_admin_even_when_roles_are_missing(): void
    {
        // Production before the role sync existed: no roles at all.
        DB::table(config('permission.table_names.model_has_roles'))->delete();
        DB::table(config('permission.table_names.role_has_permissions'))->delete();
        DB::table(config('permission.table_names.roles'))->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->artisan('clinic:create-admin', ['email' => 'Owner@Example.test', '--name' => 'Clinic Owner'])
            ->expectsQuestion('Password', self::PASSWORD)
            ->expectsQuestion('Confirm password', self::PASSWORD)
            ->assertSuccessful();

        $admin = User::sole();
        $this->assertSame('owner@example.test', $admin->email);
        $this->assertSame('Clinic Owner', $admin->name);
        $this->assertTrue($admin->hasRoleEnum(Role::Admin));
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue(Hash::check(self::PASSWORD, $admin->password));
    }

    public function test_the_admin_must_still_set_up_two_factor_before_the_clinic_opens(): void
    {
        $this->artisan('clinic:create-admin', ['email' => 'owner@example.test'])
            ->expectsQuestion('Password', self::PASSWORD)
            ->expectsQuestion('Confirm password', self::PASSWORD)
            ->assertSuccessful();

        $this->actingAs(User::sole())->get(route('clinic.dashboard'))->assertRedirect();
    }

    public function test_it_rejects_weak_or_mismatched_passwords_and_existing_emails(): void
    {
        $this->artisan('clinic:create-admin', ['email' => 'owner@example.test'])
            ->expectsQuestion('Password', self::PASSWORD)
            ->expectsQuestion('Confirm password', 'Different-Passw0rd!')
            ->assertFailed();

        User::factory()->create(['email' => 'taken@example.test']);
        $this->artisan('clinic:create-admin', ['email' => 'taken@example.test'])
            ->expectsQuestion('Password', self::PASSWORD)
            ->expectsQuestion('Confirm password', self::PASSWORD)
            ->assertFailed();

        $this->assertSame(1, User::count());
    }
}
