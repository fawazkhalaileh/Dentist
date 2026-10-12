<?php

namespace Tests\Feature\Auth;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class RoleAssignmentAndAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_self_registration_creates_a_patient_account_only(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Synthetic Person',
            'email' => 'synthetic@example.test',
            'password' => 'Strong-Passw0rd!',
            'password_confirmation' => 'Strong-Passw0rd!',
        ])->assertSessionHasNoErrors();

        $user = User::where('email', 'synthetic@example.test')->firstOrFail();

        $this->assertTrue($user->hasRole('patient'));
        $this->assertFalse($user->isStaff());
        $this->assertNull($user->patient, 'Registration must not link to a clinical record.');
    }

    public function test_weak_passwords_are_rejected(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Synthetic Person',
            'email' => 'weak@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('password');
    }

    public function test_login_is_recorded_in_the_audit_log(): void
    {
        $user = User::factory()->patient()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => 'auth.login']);
        $this->assertNotNull($user->fresh()?->last_login_at);
    }

    public function test_audit_entries_are_append_only(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $log = Audit::record('test.action');

        $this->expectException(LogicException::class);
        $log->delete();
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password']);
        }

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertTooManyRequests();

        $this->assertSame(0, AuditLog::where('action', 'auth.login')->count());
    }
}
