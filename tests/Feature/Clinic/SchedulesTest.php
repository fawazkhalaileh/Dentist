<?php

namespace Tests\Feature\Clinic;

use App\Models\AuditLog;
use App\Models\BlockedPeriod;
use App\Models\User;
use App\Support\ClinicTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class SchedulesTest extends TestCase
{
    use RefreshDatabase;

    private User $dentist;

    protected function setUp(): void
    {
        parent::setUp();

        config(['clinic.timezone' => 'Asia/Amman']);
        $this->dentist = User::factory()->dentist()->create(['name' => 'Dr. Hours']);
        $this->actingAs(User::factory()->receptionist()->withTwoFactor()->create());
    }

    public function test_weekly_hours_and_breaks_are_saved_and_reloaded(): void
    {
        Livewire::test('pages::clinic.schedules.index')
            ->assertSet('dentistId', $this->dentist->id)
            ->call('addInterval', 'hours', 0)
            ->set('hours.0.0.start', '09:00')
            ->set('hours.0.0.end', '13:00')
            ->call('addInterval', 'hours', 0)
            ->set('hours.0.1.start', '14:00')
            ->set('hours.0.1.end', '18:00')
            ->call('addInterval', 'breaks', 0)
            ->set('breaks.0.0.start', '11:00')
            ->set('breaks.0.0.end', '11:15')
            ->set('breaks.0.0.label', 'Coffee')
            ->call('copyDay', 0)
            ->call('saveSchedule')
            ->assertHasNoErrors();

        // Sunday copied to the other empty days except Friday.
        $days = DB::table('working_hours')->where('dentist_id', $this->dentist->id)->distinct()->orderBy('weekday')->pluck('weekday')->all();
        $this->assertSame([0, 1, 2, 3, 4, 6], $days);
        $this->assertSame(12, DB::table('working_hours')->count());
        $this->assertSame('Coffee', DB::table('schedule_breaks')->where('weekday', 3)->value('label'));
        $this->assertTrue(AuditLog::where('action', 'schedule.updated')->exists());

        Livewire::test('pages::clinic.schedules.index')
            ->assertSet('hours.0', [['start' => '09:00', 'end' => '13:00'], ['start' => '14:00', 'end' => '18:00']])
            ->assertSet('hours.5', []);
    }

    public function test_overlapping_or_inverted_ranges_are_rejected(): void
    {
        Livewire::test('pages::clinic.schedules.index')
            ->set('hours.1', [['start' => '09:00', 'end' => '13:00'], ['start' => '12:00', 'end' => '16:00']])
            ->call('saveSchedule')
            ->assertHasErrors(['hours.1']);

        Livewire::test('pages::clinic.schedules.index')
            ->set('hours.2', [['start' => '17:00', 'end' => '09:00']])
            ->call('saveSchedule')
            ->assertHasErrors(['hours.2']);

        Livewire::test('pages::clinic.schedules.index')
            ->set('hours.2', [['start' => '9am', 'end' => '17:00']])
            ->call('saveSchedule')
            ->assertHasErrors(['hours.2.0.start']);

        $this->assertSame(0, DB::table('working_hours')->count());
    }

    public function test_a_closure_is_stored_in_utc_from_clinic_local_times(): void
    {
        Livewire::test('pages::clinic.schedules.index')
            ->set('closure.from_date', '2026-12-25')
            ->set('closure.from_time', '00:00')
            ->set('closure.to_date', '2026-12-25')
            ->set('closure.to_time', '23:59')
            ->set('closure.reason', 'Holiday')
            ->call('addClosure')
            ->assertHasNoErrors()
            ->assertSee('Whole clinic')
            ->assertSee('2026-12-25 00:00');

        $period = BlockedPeriod::sole();
        $this->assertNull($period->dentist_id);
        // Amman is UTC+3: local midnight is 21:00 UTC the day before.
        $this->assertSame('2026-12-24 21:00', $period->starts_at->utc()->format('Y-m-d H:i'));
        $this->assertSame('2026-12-25 00:00', ClinicTime::local($period->starts_at)->format('Y-m-d H:i'));
    }

    public function test_a_closure_must_end_after_it_starts_and_target_a_dentist(): void
    {
        $receptionist = User::factory()->receptionist()->create();

        Livewire::test('pages::clinic.schedules.index')
            ->set('closure.from_date', '2026-12-25')
            ->set('closure.from_time', '12:00')
            ->set('closure.to_date', '2026-12-25')
            ->set('closure.to_time', '11:00')
            ->call('addClosure')
            ->assertHasErrors(['closure.to_time']);

        Livewire::test('pages::clinic.schedules.index')
            ->set('closure.dentist_id', (string) $receptionist->id)
            ->call('addClosure')
            ->assertHasErrors(['closure.dentist_id']);

        $this->assertSame(0, BlockedPeriod::count());
    }

    public function test_dentists_cannot_manage_schedules(): void
    {
        $this->actingAs(User::factory()->dentist()->withTwoFactor()->create());

        $this->get(route('clinic.schedules.index'))->assertForbidden();
        Livewire::test('pages::clinic.schedules.index')->assertForbidden();
    }

    public function test_only_dentists_can_be_selected(): void
    {
        $receptionist = User::factory()->receptionist()->create();

        Livewire::test('pages::clinic.schedules.index')
            ->set('dentistId', $receptionist->id)
            ->assertNotFound();
    }
}
