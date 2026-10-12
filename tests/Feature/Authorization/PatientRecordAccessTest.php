<?php

namespace Tests\Feature\Authorization;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\TreatmentItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientRecordAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $dentist;

    private User $otherDentist;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dentist = User::factory()->dentist()->withTwoFactor()->create();
        $this->otherDentist = User::factory()->dentist()->withTwoFactor()->create();
        $this->patient = Patient::factory()->assignedTo($this->dentist)->create();
    }

    public function test_assigned_dentist_can_view_the_record_and_clinical_data(): void
    {
        $this->assertTrue($this->dentist->can('view', $this->patient));
        $this->assertTrue($this->dentist->can('viewClinical', $this->patient));
        $this->assertTrue($this->dentist->can('writeClinical', $this->patient));

        $this->actingAs($this->dentist)->get(route('clinic.patients.show', $this->patient))->assertOk();
        $this->actingAs($this->dentist)->get(route('clinic.patients.chart', $this->patient))->assertOk();
    }

    public function test_unassigned_dentist_is_refused(): void
    {
        $this->assertFalse($this->otherDentist->can('view', $this->patient));
        $this->assertFalse($this->otherDentist->can('viewClinical', $this->patient));

        $this->actingAs($this->otherDentist)->get(route('clinic.patients.show', $this->patient))->assertForbidden();
        $this->actingAs($this->otherDentist)->get(route('clinic.patients.chart', $this->patient))->assertForbidden();
        $this->actingAs($this->otherDentist)->get(route('clinic.patients.notes', $this->patient))->assertForbidden();
    }

    public function test_an_appointment_puts_the_dentist_on_the_care_team(): void
    {
        Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->otherDentist->id,
        ]);

        $this->assertTrue($this->otherDentist->can('viewClinical', $this->patient));
    }

    public function test_care_team_scope_matches_the_policy(): void
    {
        Patient::factory()->count(2)->assignedTo($this->otherDentist)->create();

        $visible = Patient::query()->inCareTeamOf($this->dentist)->pluck('id')->all();

        $this->assertSame([$this->patient->id], $visible);
    }

    public function test_receptionist_sees_demographics_but_never_clinical_records(): void
    {
        $reception = User::factory()->receptionist()->withTwoFactor()->create();

        $this->assertTrue($reception->can('view', $this->patient));
        $this->assertFalse($reception->can('viewClinical', $this->patient));

        $this->actingAs($reception)->get(route('clinic.patients.show', $this->patient))->assertOk();
        $this->actingAs($reception)->get(route('clinic.patients.history', $this->patient))->assertForbidden();
        $this->actingAs($reception)->get(route('clinic.patients.chart', $this->patient))->assertForbidden();
        $this->actingAs($reception)->get(route('clinic.patients.documents', $this->patient))->assertForbidden();
    }

    public function test_admin_can_view_any_patient(): void
    {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->assertTrue($admin->can('viewClinical', $this->patient));
        $this->actingAs($admin)->get(route('clinic.patients.chart', $this->patient))->assertOk();
    }

    public function test_patient_can_see_only_their_own_record(): void
    {
        $owner = User::factory()->patient()->create();
        $ownRecord = Patient::factory()->withPortalAccount($owner)->create();

        $this->assertTrue($owner->can('view', $ownRecord));
        $this->assertFalse($owner->can('view', $this->patient));
        $this->assertFalse($owner->can('viewClinical', $ownRecord), 'Staff-side clinical record is not a portal view.');
    }

    public function test_portal_shows_the_linked_record_only(): void
    {
        $owner = User::factory()->patient()->create();
        $ownRecord = Patient::factory()->withPortalAccount($owner)->create();

        $this->actingAs($owner)->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee($ownRecord->patient_number)
            ->assertDontSee($this->patient->patient_number);
    }

    public function test_unlinked_patient_account_sees_an_explanation(): void
    {
        $this->actingAs(User::factory()->patient()->create())
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee('not linked to a patient record', escape: false);
    }

    public function test_archived_patients_are_not_reachable(): void
    {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $this->patient->delete();

        $this->actingAs($admin)->get(route('clinic.patients.show', $this->patient->id))->assertNotFound();
        $this->assertSoftDeleted($this->patient);
    }

    public function test_nobody_can_delete_treatment_items_or_force_delete_patients(): void
    {
        $admin = User::factory()->admin()->create();
        $item = TreatmentItem::factory()->create(['patient_id' => $this->patient->id, 'dentist_id' => $this->dentist->id]);

        $this->assertFalse($admin->can('delete', $item));
        $this->assertFalse($admin->can('forceDelete', $this->patient));
        $this->assertTrue($this->dentist->can('changeStatus', $item));
        $this->assertFalse($this->otherDentist->can('changeStatus', $item));
    }
}
