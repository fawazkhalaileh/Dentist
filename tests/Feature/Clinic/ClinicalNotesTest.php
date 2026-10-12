<?php

namespace Tests\Feature\Clinic;

use App\Models\AuditLog;
use App\Models\ClinicalNote;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClinicalNotesTest extends TestCase
{
    use RefreshDatabase;

    private User $dentist;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dentist = User::factory()->dentist()->withTwoFactor()->create(['name' => 'Dr. Author']);
        $this->patient = Patient::factory()->assignedTo($this->dentist)->create();
        $this->actingAs($this->dentist);
    }

    public function test_a_care_team_dentist_adds_a_note(): void
    {
        Livewire::test('pages::clinic.patients.notes', ['patient' => $this->patient])
            ->assertSee('No clinical notes yet')
            ->set('body', 'Caries on 36, filling planned.')
            ->call('add')
            ->assertHasNoErrors()
            ->assertSet('body', '')
            ->assertSee('Caries on 36, filling planned.')
            ->assertSee('Dr. Author');

        $note = ClinicalNote::sole();
        $this->assertSame($this->dentist->id, $note->author_id);
        $this->assertTrue(AuditLog::where('action', 'clinical.note_created')->where('patient_id', $this->patient->id)->exists());
        $this->assertStringNotContainsString('Caries', (string) AuditLog::pluck('metadata')->toJson());
    }

    public function test_an_empty_note_is_rejected(): void
    {
        Livewire::test('pages::clinic.patients.notes', ['patient' => $this->patient])
            ->set('body', '   ')
            ->call('add')
            ->assertHasErrors(['body']);
    }

    public function test_a_correction_replaces_the_note_but_keeps_the_original(): void
    {
        $original = $this->note('Filling on 26.', $this->dentist);

        Livewire::test('pages::clinic.patients.notes', ['patient' => $this->patient])
            ->call('startAmend', $original->id)
            ->assertSet('amendment', 'Filling on 26.')
            ->set('amendment', 'Filling on 36.')
            ->call('saveAmendment')
            ->assertHasNoErrors()
            ->assertSee('Filling on 36.')
            ->assertSee('Corrected')
            ->assertDontSee('Filling on 26.')
            ->call('toggleVersions', ClinicalNote::latest('id')->value('id'))
            ->assertSee('Filling on 26.');

        $this->assertSame(2, ClinicalNote::count());
        $this->assertSame('Filling on 26.', $original->fresh()?->body);
        $this->assertSame($original->id, ClinicalNote::latest('id')->value('amends_id'));
        $this->assertTrue(AuditLog::where('action', 'clinical.note_amended')->exists());
    }

    public function test_a_correction_must_change_the_text(): void
    {
        $original = $this->note('Same text', $this->dentist);

        Livewire::test('pages::clinic.patients.notes', ['patient' => $this->patient])
            ->call('startAmend', $original->id)
            ->call('saveAmendment')
            ->assertHasErrors(['amendment']);
    }

    public function test_only_the_author_or_an_admin_can_correct_a_note(): void
    {
        $colleague = User::factory()->dentist()->withTwoFactor()->create();
        $this->patient->dentists()->attach($colleague);
        $note = $this->note('Colleague note', $colleague);

        Livewire::test('pages::clinic.patients.notes', ['patient' => $this->patient])
            ->assertDontSee('Correct</')
            ->call('startAmend', $note->id)
            ->assertForbidden();

        $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
        Livewire::test('pages::clinic.patients.notes', ['patient' => $this->patient])
            ->call('startAmend', $note->id)
            ->set('amendment', 'Colleague note, corrected by admin')
            ->call('saveAmendment')
            ->assertHasNoErrors();
    }

    public function test_only_the_latest_version_can_be_corrected(): void
    {
        $original = $this->note('v1', $this->dentist);
        $this->patient->clinicalNotes()->create(['author_id' => $this->dentist->id, 'amends_id' => $original->id, 'body' => 'v2']);

        Livewire::test('pages::clinic.patients.notes', ['patient' => $this->patient])
            ->call('startAmend', $original->id)
            ->assertForbidden();
    }

    public function test_notes_of_other_patients_cannot_be_targeted(): void
    {
        $other = Patient::factory()->assignedTo($this->dentist)->create();
        $foreign = $other->clinicalNotes()->create(['author_id' => $this->dentist->id, 'body' => 'Other patient']);

        Livewire::test('pages::clinic.patients.notes', ['patient' => $this->patient])
            ->call('startAmend', $foreign->id)
            ->assertNotFound();
    }

    public function test_receptionists_and_dentists_outside_the_care_team_cannot_open_notes(): void
    {
        $this->note('Private', $this->dentist);

        $this->actingAs(User::factory()->receptionist()->withTwoFactor()->create());
        $this->get(route('clinic.patients.notes', $this->patient))->assertForbidden();

        $this->actingAs(User::factory()->dentist()->withTwoFactor()->create());
        $this->get(route('clinic.patients.notes', $this->patient))->assertForbidden();
    }

    public function test_opening_notes_is_audited(): void
    {
        $this->get(route('clinic.patients.notes', $this->patient))->assertOk();

        $this->assertTrue(AuditLog::where('action', 'clinical.notes_viewed')->where('patient_id', $this->patient->id)->exists());
    }

    private function note(string $body, User $author): ClinicalNote
    {
        return $this->patient->clinicalNotes()->create(['author_id' => $author->id, 'body' => $body]);
    }
}
