<?php

namespace Tests\Feature\Clinic;

use App\Models\AuditLog;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

class PatientHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $dentist;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dentist = User::factory()->dentist()->withTwoFactor()->create(['name' => 'Dr. Hist']);
        $this->patient = Patient::factory()->assignedTo($this->dentist)->create();
        $this->actingAs($this->dentist);
    }

    public function test_a_care_team_dentist_records_a_medical_history(): void
    {
        Livewire::test('pages::clinic.patients.history', ['patient' => $this->patient])
            ->assertSee('No medical history has been recorded')
            ->call('startReview', 'medical')
            ->set('medical.allergies', 'Penicillin (rash)')
            ->set('medical.medications', 'Metformin')
            ->call('saveMedical')
            ->assertHasNoErrors()
            ->assertSee('Penicillin (rash)')
            ->assertSee('Dr. Hist');

        $history = MedicalHistory::sole();
        $this->assertSame($this->dentist->id, $history->recorded_by);
        $this->assertTrue(AuditLog::where('action', 'clinical.medical_history_recorded')->where('patient_id', $this->patient->id)->exists());
    }

    public function test_a_review_adds_a_version_and_keeps_the_earlier_one(): void
    {
        $component = Livewire::test('pages::clinic.patients.history', ['patient' => $this->patient])
            ->call('startReview', 'medical')->set('medical.allergies', 'Latex')->call('saveMedical');

        $component->call('startReview', 'medical')
            ->assertSet('medical.allergies', 'Latex')
            ->set('medical.allergies', 'Latex, penicillin')
            ->call('saveMedical')
            ->assertSee('Earlier versions');

        $this->assertSame(['Latex, penicillin', 'Latex'], MedicalHistory::orderByDesc('id')->get()->pluck('allergies')->all());
        $this->assertSame('Latex, penicillin', $this->patient->fresh()?->latestMedicalHistory?->allergies);
    }

    public function test_history_versions_cannot_be_changed_or_deleted(): void
    {
        $history = $this->patient->medicalHistories()->create(['recorded_by' => $this->dentist->id, 'allergies' => 'Latex']);

        try {
            $history->update(['allergies' => 'None']);
            $this->fail('Medical history versions must be append-only.');
        } catch (LogicException) {
        }

        $this->expectException(LogicException::class);
        $history->delete();
    }

    public function test_no_known_allergies_cannot_be_combined_with_listed_allergies(): void
    {
        Livewire::test('pages::clinic.patients.history', ['patient' => $this->patient])
            ->call('startReview', 'medical')
            ->set('medical.allergies', 'Latex')
            ->set('medical.no_known_allergies', true)
            ->call('saveMedical')
            ->assertHasErrors(['medical.allergies']);

        $this->assertSame(0, MedicalHistory::count());
    }

    public function test_the_overview_warns_about_allergies_or_missing_allergy_information(): void
    {
        $this->get(route('clinic.patients.show', $this->patient))->assertSee('Allergies have not been recorded');

        $this->patient->medicalHistories()->create(['recorded_by' => $this->dentist->id, 'no_known_allergies' => true]);
        $this->get(route('clinic.patients.show', $this->patient))->assertDontSee('Allergies have not been recorded');

        $this->patient->medicalHistories()->create(['recorded_by' => $this->dentist->id, 'allergies' => 'Penicillin']);
        $this->get(route('clinic.patients.show', $this->patient))->assertSee('Penicillin');
    }

    public function test_receptionists_see_neither_the_history_nor_the_allergy_banner(): void
    {
        $this->patient->medicalHistories()->create(['recorded_by' => $this->dentist->id, 'allergies' => 'Secret allergy']);
        $this->actingAs(User::factory()->receptionist()->withTwoFactor()->create());

        $this->get(route('clinic.patients.history', $this->patient))->assertForbidden();
        $this->get(route('clinic.patients.show', $this->patient))->assertOk()->assertDontSee('Secret allergy')->assertDontSee('Allergies have not been recorded');
        Livewire::test('pages::clinic.patients.history', ['patient' => $this->patient])->assertForbidden();
    }

    public function test_dentists_outside_the_care_team_cannot_read_or_write(): void
    {
        $this->actingAs(User::factory()->dentist()->withTwoFactor()->create());

        $this->get(route('clinic.patients.history', $this->patient))->assertForbidden();
    }

    public function test_admins_can_read_and_write_history(): void
    {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());

        Livewire::test('pages::clinic.patients.history', ['patient' => $this->patient])
            ->call('startReview', 'dental')
            ->set('dental.chief_complaint', 'Toothache lower left')
            ->call('saveDental')
            ->assertHasNoErrors()
            ->assertSee('Toothache lower left');
    }

    public function test_opening_the_history_is_audited_and_its_text_is_encrypted_at_rest(): void
    {
        $this->patient->medicalHistories()->create(['recorded_by' => $this->dentist->id, 'allergies' => 'Plaintext allergy']);

        $this->get(route('clinic.patients.history', $this->patient))->assertOk()->assertSee('Plaintext allergy');

        $this->assertTrue(AuditLog::where('action', 'clinical.history_viewed')->where('patient_id', $this->patient->id)->exists());
        $raw = (string) DB::table('medical_histories')->value('allergies');
        $this->assertStringNotContainsString('Plaintext allergy', $raw);
        $this->assertStringNotContainsString('allergy', (string) AuditLog::pluck('metadata')->toJson());
    }

    public function test_versions_of_other_patients_cannot_be_opened(): void
    {
        $other = Patient::factory()->create();
        $foreign = $other->medicalHistories()->create(['recorded_by' => $this->dentist->id, 'allergies' => 'Other patient allergy']);

        Livewire::test('pages::clinic.patients.history', ['patient' => $this->patient])
            ->call('showVersion', 'medical', $foreign->id)
            ->assertNotFound();
    }
}
