<?php

namespace Tests\Feature\Clinic;

use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PatientDirectoryTest extends TestCase
{
    use RefreshDatabase;

    private User $reception;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reception = User::factory()->receptionist()->withTwoFactor()->create();
        $this->actingAs($this->reception);
    }

    public function test_search_finds_patients_by_name_number_phone_and_email(): void
    {
        $sara = Patient::factory()->create(['first_name' => 'Sara', 'last_name' => 'Nasser', 'phone' => '+962 79 123 4567', 'email' => 'sara@example.test']);
        // Keep the number search independent of auto-increment IDs that may match phone digits.
        $sara->forceFill(['patient_number' => 'P-SEARCH-ONLY'])->save();
        Patient::factory()->create(['first_name' => 'Omar', 'last_name' => 'Khalil', 'phone' => '+962 77 000 1111', 'email' => 'omar@example.test']);

        foreach (['Sara Nasser', 'Nasser Sara', 'nass', $sara->patient_number, '0791234567', '79 123', 'sara@example'] as $term) {
            Livewire::test('pages::clinic.patients.index')
                ->set('search', $term)
                ->assertSee('Sara Nasser')
                ->assertDontSee('Omar Khalil');
        }
    }

    public function test_search_wildcards_are_treated_literally(): void
    {
        Patient::factory()->create(['first_name' => 'Plain', 'last_name' => 'Name']);

        Livewire::test('pages::clinic.patients.index')->set('search', '%')->assertDontSee('Plain Name');
    }

    public function test_dentists_only_see_their_care_team_in_the_list(): void
    {
        $dentist = User::factory()->dentist()->withTwoFactor()->create();
        Patient::factory()->assignedTo($dentist)->create(['first_name' => 'Mine', 'last_name' => 'Patient']);
        Patient::factory()->create(['first_name' => 'Other', 'last_name' => 'Patient']);

        $this->actingAs($dentist);
        Livewire::test('pages::clinic.patients.index')->assertSee('Mine Patient')->assertDontSee('Other Patient');
    }

    public function test_a_patient_is_registered_with_a_number_care_team_and_audit_entry(): void
    {
        $dentist = User::factory()->dentist()->create(['name' => 'Dr. Care']);

        Livewire::test('pages::clinic.patients.edit')
            ->set('form.first_name', 'Lina')
            ->set('form.last_name', 'Saleh')
            ->set('form.date_of_birth', '1990-05-01')
            ->set('form.phone', '+962 79 555 0000')
            ->set('form.dentist_ids', [$dentist->id])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $patient = Patient::sole();
        $this->assertMatchesRegularExpression('/^P-0*'.$patient->id.'$/', $patient->patient_number);
        $this->assertSame($this->reception->id, $patient->created_by);
        $this->assertSame([$dentist->id], $patient->dentists()->pluck('users.id')->all());
        $this->assertTrue(AuditLog::where('action', 'patient.created')->where('patient_id', $patient->id)->exists());

        $this->get(route('clinic.patients.show', $patient))->assertOk()->assertSee('Lina Saleh')->assertSee('Dr. Care');
    }

    public function test_a_phone_or_email_is_required_and_dates_of_birth_cannot_be_in_the_future(): void
    {
        Livewire::test('pages::clinic.patients.edit')
            ->set('form.first_name', 'No')
            ->set('form.last_name', 'Contact')
            ->set('form.date_of_birth', now()->addDay()->format('Y-m-d'))
            ->call('save')
            ->assertHasErrors(['form.phone', 'form.email', 'form.date_of_birth']);

        $this->assertSame(0, Patient::count());
    }

    public function test_possible_duplicates_are_shown_before_registering(): void
    {
        $existing = Patient::factory()->create(['first_name' => 'Sara', 'last_name' => 'Nasser', 'phone' => '+962 79 123 4567']);

        $component = Livewire::test('pages::clinic.patients.edit')
            ->set('form.first_name', 'S.')
            ->set('form.last_name', 'Nasser')
            ->set('form.phone', '079 123 4567')
            ->call('save')
            ->assertSet('duplicateIds', [$existing->id])
            ->assertSee($existing->patient_number);

        $this->assertSame(1, Patient::count());

        $component->call('confirmNotDuplicate')->assertHasNoErrors()->assertRedirect();
        $this->assertSame(2, Patient::count());
    }

    public function test_same_name_and_date_of_birth_counts_as_a_possible_duplicate(): void
    {
        Patient::factory()->create(['first_name' => 'Omar', 'last_name' => 'Khalil', 'date_of_birth' => '1985-02-03', 'phone' => '111111111']);

        Livewire::test('pages::clinic.patients.edit')
            ->set('form.first_name', 'Omar')
            ->set('form.last_name', 'Khalil')
            ->set('form.date_of_birth', '1985-02-03')
            ->set('form.email', 'omar@example.test')
            ->call('save')
            ->assertNotSet('duplicateIds', []);

        $this->assertSame(1, Patient::count());
    }

    public function test_editing_audits_which_fields_changed_but_not_their_values(): void
    {
        $patient = Patient::factory()->create(['phone' => '+962 79 000 0000']);

        Livewire::test('pages::clinic.patients.edit', ['patient' => $patient])
            ->assertSet('form.phone', '+962 79 000 0000')
            ->set('form.phone', '+962 79 999 9999')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('+962 79 999 9999', $patient->fresh()?->phone);
        $entry = AuditLog::where('action', 'patient.updated')->sole();
        $this->assertSame('phone', $entry->metadata['fields'] ?? null);
        $this->assertStringNotContainsString('999', (string) json_encode($entry->metadata));
    }

    public function test_the_care_team_only_accepts_dentists(): void
    {
        $patient = Patient::factory()->create();
        $notADentist = User::factory()->receptionist()->create();

        Livewire::test('pages::clinic.patients.edit', ['patient' => $patient])
            ->set('form.dentist_ids', [$notADentist->id])
            ->call('save')
            ->assertHasErrors(['form.dentist_ids.0']);

        $this->assertSame(0, $patient->dentists()->count());
    }

    public function test_a_tampered_form_id_cannot_redirect_the_save_to_another_patient(): void
    {
        $patient = Patient::factory()->create(['first_name' => 'Target']);
        $other = Patient::factory()->create(['first_name' => 'Other']);

        Livewire::test('pages::clinic.patients.edit', ['patient' => $patient])
            ->set('form.patientId', $other->id)
            ->set('form.first_name', 'Changed')
            ->call('save');

        $this->assertSame('Changed', $patient->fresh()?->first_name);
        $this->assertSame('Other', $other->fresh()?->first_name);
    }

    public function test_dentists_cannot_register_or_edit_patients(): void
    {
        $dentist = User::factory()->dentist()->withTwoFactor()->create();
        $patient = Patient::factory()->assignedTo($dentist)->create();
        $this->actingAs($dentist);

        $this->get(route('clinic.patients.create'))->assertForbidden();
        $this->get(route('clinic.patients.edit', $patient))->assertForbidden();
        $this->get(route('clinic.patients.show', $patient))->assertOk()->assertDontSee(route('clinic.patients.edit', $patient));
    }

    public function test_opening_a_record_is_audited(): void
    {
        $patient = Patient::factory()->create();

        $this->get(route('clinic.patients.show', $patient))->assertOk();

        $this->assertTrue(AuditLog::where('action', 'patient.viewed')->where('patient_id', $patient->id)->where('user_id', $this->reception->id)->exists());
    }

    public function test_administrators_archive_and_restore_patients_and_receptionists_cannot(): void
    {
        $patient = Patient::factory()->create(['first_name' => 'Archived', 'last_name' => 'Person']);

        Livewire::test('pages::clinic.patients.show', ['patient' => $patient])->call('archive')->assertForbidden();
        $this->assertNotSoftDeleted($patient);

        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        Livewire::test('pages::clinic.patients.show', ['patient' => $patient])->call('archive')->assertRedirect(route('clinic.patients.index'));
        $this->assertSoftDeleted($patient);
        session()->forget('status'); // the archive confirmation names the patient

        Livewire::test('pages::clinic.patients.index')->assertDontSee('Archived Person')
            ->set('showArchived', true)->assertSee('Archived Person')
            ->call('restore', $patient->id);

        $this->assertNotSoftDeleted($patient);
        $this->assertTrue(AuditLog::where('action', 'patient.archived')->exists());
        $this->assertTrue(AuditLog::where('action', 'patient.restored')->exists());
    }

    public function test_receptionists_cannot_list_archived_patients(): void
    {
        $patient = Patient::factory()->create(['first_name' => 'Hidden', 'last_name' => 'Archive']);
        $patient->delete();

        Livewire::test('pages::clinic.patients.index')->set('showArchived', true)->assertDontSee('Hidden Archive');
        Livewire::test('pages::clinic.patients.index')->call('restore', $patient->id)->assertForbidden();
    }
}
