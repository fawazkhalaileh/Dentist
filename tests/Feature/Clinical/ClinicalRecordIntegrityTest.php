<?php

namespace Tests\Feature\Clinical;

use App\Enums\TreatmentEventType;
use App\Enums\TreatmentStatus;
use App\Models\ClinicalNote;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\TreatmentEvent;
use App\Models\TreatmentItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class ClinicalRecordIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_teeth_reference_table_holds_fdi_numbers(): void
    {
        $this->assertSame(32, DB::table('teeth')->where('dentition', 'permanent')->count());
        $this->assertSame(20, DB::table('teeth')->where('dentition', 'primary')->count());
        $this->assertSame('Upper right central incisor', DB::table('teeth')->where('fdi_number', 11)->value('name'));
    }

    public function test_a_tooth_can_hold_several_treatment_items_with_separate_histories(): void
    {
        $patient = Patient::factory()->create();
        $first = TreatmentItem::factory()->create(['patient_id' => $patient->id, 'tooth_number' => 11]);
        $second = TreatmentItem::factory()->create(['patient_id' => $patient->id, 'tooth_number' => 11, 'status' => TreatmentStatus::Completed]);

        $this->assertSame(2, $patient->treatmentItems()->where('tooth_number', 11)->count());
        $this->assertNotSame($first->status, $second->status);
    }

    public function test_treatment_events_cannot_be_changed_or_removed(): void
    {
        $item = TreatmentItem::factory()->create();
        $event = TreatmentEvent::create([
            'treatment_item_id' => $item->id,
            'type' => TreatmentEventType::Created,
            'to_status' => TreatmentStatus::Planned,
            'performed_by' => $item->dentist_id,
            'occurred_at' => now(),
        ]);

        try {
            $event->update(['to_status' => TreatmentStatus::Completed]);
            $this->fail('Updating a treatment event should throw.');
        } catch (LogicException) {
        }

        try {
            $event->delete();
            $this->fail('Deleting a treatment event should throw.');
        } catch (LogicException) {
        }

        $this->assertSame(TreatmentStatus::Planned, $event->fresh()?->to_status);
    }

    public function test_treatment_items_cannot_be_deleted(): void
    {
        $item = TreatmentItem::factory()->create();

        $this->expectException(LogicException::class);
        $item->delete();
    }

    public function test_clinical_notes_cannot_be_edited(): void
    {
        $note = ClinicalNote::create([
            'patient_id' => Patient::factory()->create()->id,
            'author_id' => User::factory()->dentist()->create()->id,
            'body' => 'Original note',
        ]);

        $this->expectException(LogicException::class);
        $note->update(['body' => 'Changed']);
    }

    public function test_sensitive_clinical_text_is_encrypted_at_rest(): void
    {
        $history = MedicalHistory::create([
            'patient_id' => Patient::factory()->create()->id,
            'recorded_by' => User::factory()->dentist()->create()->id,
            'allergies' => 'Synthetic allergy text',
        ]);

        $raw = DB::table('medical_histories')->where('id', $history->id)->value('allergies');

        $this->assertNotSame('Synthetic allergy text', $raw);
        $this->assertSame('Synthetic allergy text', $history->fresh()?->allergies);
    }

    public function test_patient_numbers_are_unique_and_derived_from_the_id(): void
    {
        $a = Patient::factory()->create();
        $b = Patient::factory()->create();

        $this->assertSame(Patient::formatNumber($a->id), $a->fresh()?->patient_number);
        $this->assertMatchesRegularExpression('/^P-\d{6}$/', (string) $b->fresh()?->patient_number);
        $this->assertNotSame($a->patient_number, $b->patient_number);
    }
}
