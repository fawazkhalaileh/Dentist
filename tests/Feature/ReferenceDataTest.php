<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Features;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\CreatesContent;
use Tests\TestCase;

/**
 * Production runs migrations but no seeders. Reference data the app needs must
 * come from `migrate`, and public pages must survive if it is missing.
 */
class ReferenceDataTest extends TestCase
{
    use CreatesContent, RefreshDatabase;

    public function test_migrations_alone_create_every_role_and_permission_without_users(): void
    {
        foreach (Role::cases() as $role) {
            $model = RoleModel::findByName($role->value, 'web');
            $this->assertEqualsCanonicalizing(
                array_map(fn (Permission $p) => $p->value, $role->permissions()),
                $model->permissions->pluck('name')->all(),
                "Role {$role->value} has the wrong permissions.",
            );
        }

        $this->assertSame(count(Permission::cases()), PermissionModel::count());
        $this->assertSame(0, User::count(), 'Migrations must never create users.');
    }

    public function test_migrate_restores_missing_roles_even_with_nothing_to_migrate(): void
    {
        $this->deleteRolesAndPermissions();

        $this->artisan('migrate')->assertSuccessful();
        // A second run must not fail or duplicate anything.
        $this->artisan('migrate')->assertSuccessful();

        $this->assertSame(count(Role::cases()), RoleModel::count());
        $this->assertSame(count(Permission::cases()), PermissionModel::count());
        $this->assertSame(0, User::count());
    }

    public function test_public_pages_do_not_crash_when_roles_are_missing(): void
    {
        // The production state that caused a 500: content exists but no roles were created.
        $this->deleteRolesAndPermissions();

        $user = User::factory()->create();
        $profile = StaffProfile::factory()->create(['user_id' => $user->id, 'slug' => 'dr-test', 'is_public' => true]);
        $profile->translations()->create(['locale' => 'en', 'display_name' => 'Dr. Test']);
        $this->makeService(['en' => 'Cleaning']);

        $this->get('/en')->assertOk();
        $this->get('/ar')->assertOk();
        $this->get('/en/dentists')->assertOk()->assertDontSee('Dr. Test');
        $this->get('/en/dentists/dr-test')->assertNotFound();
        $this->get('/en/services/cleaning')->assertOk();
        $this->get(route('sitemap'))->assertOk();
    }

    public function test_a_failed_registration_leaves_no_account_without_a_role(): void
    {
        $this->skipUnlessFortifyHas(Features::registration());
        $this->deleteRolesAndPermissions();

        $this->post(route('register.store'), [
            'name' => 'Test Patient',
            'email' => 'patient@example.test',
            'password' => 'New-Passw0rd!',
            'password_confirmation' => 'New-Passw0rd!',
        ])->assertServerError();

        $this->assertSame(0, User::count());
    }

    private function deleteRolesAndPermissions(): void
    {
        DB::table(config('permission.table_names.role_has_permissions'))->delete();
        DB::table(config('permission.table_names.model_has_roles'))->delete();
        DB::table(config('permission.table_names.model_has_permissions'))->delete();
        DB::table(config('permission.table_names.roles'))->delete();
        DB::table(config('permission.table_names.permissions'))->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
