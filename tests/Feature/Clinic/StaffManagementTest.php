<?php

namespace Tests\Feature\Clinic;

use App\Actions\Staff\SaveStaffMember;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->withTwoFactor()->create(['name' => 'Clinic Admin']);
        $this->actingAs($this->admin);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonAdminRoles(): array
    {
        return ['dentist' => ['dentist'], 'receptionist' => ['receptionist']];
    }

    #[DataProvider('nonAdminRoles')]
    public function test_only_administrators_can_manage_staff(string $role): void
    {
        $user = User::factory()->withRole(Role::from($role))->withTwoFactor()->create();
        $this->actingAs($user);

        $this->get(route('clinic.staff.index'))->assertForbidden();
        $this->get(route('clinic.staff.create'))->assertForbidden();
        $this->get(route('clinic.staff.edit', $this->admin))->assertForbidden();
        Livewire::test('pages::clinic.staff.index')->assertForbidden();
    }

    public function test_the_staff_list_shows_staff_but_not_patients(): void
    {
        User::factory()->dentist()->create(['name' => 'Dr. Listed']);
        User::factory()->patient()->create(['name' => 'Portal Patient']);
        User::factory()->receptionist()->create(['name' => 'Gone Receptionist', 'is_active' => false]);

        $this->get(route('clinic.staff.index'))
            ->assertOk()
            ->assertSee('Dr. Listed')
            ->assertDontSee('Portal Patient')
            ->assertDontSee('Gone Receptionist');

        Livewire::test('pages::clinic.staff.index')
            ->set('showInactive', true)->assertSee('Gone Receptionist')
            ->set('search', 'Listed')->assertSee('Dr. Listed')->assertDontSee('Clinic Admin');
    }

    public function test_patient_accounts_cannot_be_opened_in_the_staff_editor(): void
    {
        $patient = User::factory()->patient()->create();

        $this->get(route('clinic.staff.edit', $patient))->assertNotFound();
    }

    public function test_a_receptionist_is_created_verified_without_a_usable_password_and_audited(): void
    {
        Livewire::test('pages::clinic.staff.edit')
            ->set('form.name', 'Rana Reception')
            ->set('form.email', 'Rana@Example.test')
            ->set('form.role', 'receptionist')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $user = User::where('email', 'rana@example.test')->sole();
        $this->assertTrue($user->hasRoleEnum(Role::Receptionist));
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->staffProfile);
        $this->assertTrue(AuditLog::where('action', 'staff.created')->where('auditable_id', $user->id)->exists());
    }

    public function test_a_dentist_profile_is_created_and_shown_on_the_website(): void
    {
        Storage::fake('public');

        Livewire::test('pages::clinic.staff.edit')
            ->set('form.name', 'Dr. Lina Haddad')
            ->set('form.email', 'lina@example.test')
            ->set('form.role', 'dentist')
            ->set('form.is_public', true)
            ->set('form.translations.en.specialty', 'Orthodontics')
            ->set('form.translations.ar.display_name', 'د. لينا حداد')
            ->set('photoUpload', UploadedFile::fake()->image('lina.jpg', 600, 600))
            ->call('save')
            ->assertHasNoErrors();

        $profile = StaffProfile::with('translations')->sole();
        $this->assertSame('dr-lina-haddad', $profile->slug);
        $this->assertSame('Dr. Lina Haddad', $profile->translation('en')?->display_name, 'The default-language name falls back to the full name.');
        $this->assertNotNull($profile->photo_media_id);

        $this->get(route('public.dentists.show', ['locale' => 'en', 'slug' => 'dr-lina-haddad']))->assertOk()->assertSee('Orthodontics');
        $this->get(route('public.dentists.index', ['locale' => 'ar']))->assertSee('د. لينا حداد');
    }

    public function test_changing_a_dentist_to_another_role_hides_the_profile(): void
    {
        $dentist = $this->makeDentist();

        Livewire::test('pages::clinic.staff.edit', ['user' => $dentist])
            ->set('form.role', 'receptionist')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue($dentist->fresh()?->hasRoleEnum(Role::Receptionist));
        $this->assertFalse((bool) $dentist->staffProfile()->value('is_public'));
        $this->get(route('public.dentists.show', ['locale' => 'en', 'slug' => 'dr-public']))->assertNotFound();
    }

    public function test_deactivating_signs_the_person_out_and_hides_their_profile(): void
    {
        $dentist = $this->makeDentist();
        config(['session.driver' => 'database']); // as in production
        DB::table('sessions')->insert(['id' => 'abc', 'user_id' => $dentist->id, 'payload' => '', 'last_activity' => time()]);

        Livewire::test('pages::clinic.staff.edit', ['user' => $dentist])
            ->set('form.is_active', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse($dentist->fresh()?->is_active);
        $this->assertSame(0, DB::table('sessions')->where('user_id', $dentist->id)->count());
        $this->get(route('public.dentists.show', ['locale' => 'en', 'slug' => 'dr-public']))->assertNotFound();
        $this->assertTrue(AuditLog::where('action', 'staff.updated')->where('auditable_id', $dentist->id)->exists());

        $this->actingAs($dentist->fresh())->get(route('clinic.dashboard'))->assertRedirect(route('login'));
    }

    public function test_administrators_cannot_demote_or_deactivate_themselves(): void
    {
        User::factory()->admin()->create();

        Livewire::test('pages::clinic.staff.edit', ['user' => $this->admin])
            ->set('form.role', 'receptionist')
            ->call('save')
            ->assertHasErrors(['form.role']);

        Livewire::test('pages::clinic.staff.edit', ['user' => $this->admin])
            ->set('form.is_active', false)
            ->call('save')
            ->assertHasErrors(['form.role']);

        $this->assertTrue($this->admin->fresh()?->hasRoleEnum(Role::Admin));
        $this->assertTrue($this->admin->fresh()?->is_active);
    }

    public function test_the_last_active_administrator_cannot_be_removed(): void
    {
        // Through the screens an acting admin always remains an active admin, so test the
        // action's own guard directly (defence in depth if permissions ever change).
        $actor = User::factory()->receptionist()->create();
        $payload = fn (array $overrides) => [
            'name' => $this->admin->name, 'email' => $this->admin->email, 'role' => 'admin',
            'is_active' => true, 'locale' => 'en', 'profile' => null, ...$overrides,
        ];

        foreach ([['role' => 'dentist'], ['is_active' => false]] as $change) {
            try {
                app(SaveStaffMember::class)->handle($payload($change), $actor, $this->admin);
                $this->fail('Removing the last active administrator must be refused.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('form.role', $e->errors());
            }
        }

        // With a second active administrator the same change is allowed.
        User::factory()->admin()->create();
        app(SaveStaffMember::class)->handle($payload(['role' => 'dentist']), $actor, $this->admin);
        $this->assertTrue($this->admin->fresh()?->hasRoleEnum(Role::Dentist));
    }

    public function test_a_setup_link_lets_the_person_choose_a_password(): void
    {
        $receptionist = User::factory()->receptionist()->create(['email' => 'new@example.test']);

        $component = Livewire::test('pages::clinic.staff.edit', ['user' => $receptionist])->call('createSetupLink');
        $link = (string) $component->get('setupLink');

        $this->assertStringContainsString('/reset-password/', $link);
        $this->assertTrue(AuditLog::where('action', 'staff.setup_link_created')->where('auditable_id', $receptionist->id)->exists());

        preg_match('#/reset-password/([^?]+)#', $link, $m);
        auth()->logout();
        $this->post(route('password.update'), [
            'token' => $m[1],
            'email' => 'new@example.test',
            'password' => 'Chosen-Passw0rd!',
            'password_confirmation' => 'Chosen-Passw0rd!',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('Chosen-Passw0rd!', (string) $receptionist->fresh()?->password));
    }

    public function test_deactivated_accounts_get_no_setup_link(): void
    {
        $receptionist = User::factory()->receptionist()->create(['is_active' => false]);

        Livewire::test('pages::clinic.staff.edit', ['user' => $receptionist])->call('createSetupLink')->assertStatus(422);
        $this->assertFalse(Password::broker()->getRepository()->recentlyCreatedToken($receptionist));
    }

    public function test_two_factor_can_be_reset_for_others_but_not_yourself(): void
    {
        $dentist = User::factory()->dentist()->withTwoFactor()->create();

        Livewire::test('pages::clinic.staff.edit', ['user' => $dentist])->call('resetTwoFactor')->assertHasNoErrors();
        $this->assertNull($dentist->fresh()?->two_factor_confirmed_at);
        $this->assertTrue(AuditLog::where('action', 'staff.two_factor_reset')->exists());

        Livewire::test('pages::clinic.staff.edit', ['user' => $this->admin])->call('resetTwoFactor')->assertStatus(422);
        $this->assertNotNull($this->admin->fresh()?->two_factor_confirmed_at);
    }

    public function test_emails_and_profile_urls_must_be_unique(): void
    {
        $this->makeDentist();

        Livewire::test('pages::clinic.staff.edit')
            ->set('form.name', 'Someone')
            ->set('form.email', $this->admin->email)
            ->set('form.role', 'dentist')
            ->set('form.slug', 'dr-public')
            ->call('save')
            ->assertHasErrors(['form.email', 'form.slug']);
    }

    private function makeDentist(): User
    {
        $dentist = User::factory()->dentist()->create(['name' => 'Dr. Public']);
        $profile = StaffProfile::factory()->create(['user_id' => $dentist->id, 'slug' => 'dr-public', 'is_public' => true]);
        $profile->translations()->create(['locale' => 'en', 'display_name' => 'Dr. Public']);

        $this->get(route('public.dentists.show', ['locale' => 'en', 'slug' => 'dr-public']))->assertOk();

        return $dentist;
    }
}
