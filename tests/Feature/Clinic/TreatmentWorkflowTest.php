<?php

namespace Tests\Feature\Clinic;

use App\Actions\Treatments\ChangeTreatmentStatus;
use App\Enums\TreatmentPlanStatus;
use App\Enums\TreatmentStatus;
use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\TreatmentEvent;
use App\Models\TreatmentItem;
use App\Models\TreatmentPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class TreatmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $dentist;

    private Patient $patient;

    private Procedure $filling;

    private Procedure $exam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dentist = User::factory()->dentist()->withTwoFactor()->create(['name' => 'Dr. Treat']);
        $this->patient = Patient::factory()->assignedTo($this->dentist)->create();
        $this->filling = Procedure::factory()->create(['code' => 'FILL', 'name' => 'Filling', 'scope' => 'surface']);
        $this->exam = Procedure::factory()->create(['code' => 'EXAM', 'name' => 'Examination', 'scope' => 'mouth']);
        $this->actingAs($this->dentist);
    }

    public function test_a_filling_is_planned_on_a_tooth_with_surfaces(): void
    {
        Livewire::test('pages::clinic.patients.treatments', ['patient' => $this->patient])
            ->call('openItemForm')
            ->assertSet('item.dentist_id', $this->dentist->id)
            ->set('item.procedure_id', $this->filling->id)
            ->set('item.tooth_number', 36)
            ->set('item.surfaces', ['M', 'O'])
            ->call('addItem')
            ->assertHasNoErrors()
            ->assertSee('Filling');

        $item = TreatmentItem::with('surfaces', 'events')->sole();
        $this->assertSame(TreatmentStatus::Planned, $item->status);
        $this->assertSame(36, $item->tooth_number);
        $this->assertEqualsCanonicalizing(['M', 'O'], $item->surfaces->map(fn ($s) => $s->surface->value)->all());
        $this->assertCount(1, $item->events);
        $this->assertTrue(AuditLog::where('action', 'treatment.item_created')->where('patient_id', $this->patient->id)->exists());
    }

    public function test_surfaces_must_exist_on_the_tooth(): void
    {
        // An incisor has an incisal edge, not an occlusal surface.
        Livewire::test('pages::clinic.patients.treatments', ['patient' => $this->patient])
            ->call('openItemForm')
            ->set('item.procedure_id', $this->filling->id)
            ->set('item.tooth_number', 11)
            ->set('item.surfaces', ['O'])
            ->call('addItem')
            ->assertHasErrors(['item.surfaces.0']);

        $this->assertSame(0, TreatmentItem::count());
    }

    public function test_tooth_procedures_need_a_tooth_and_mouth_procedures_ignore_it(): void
    {
        Livewire::test('pages::clinic.patients.treatments', ['patient' => $this->patient])
            ->call('openItemForm')
            ->set('item.procedure_id', $this->filling->id)
            ->call('addItem')
            ->assertHasErrors(['item.tooth_number', 'item.surfaces']);

        Livewire::test('pages::clinic.patients.treatments', ['patient' => $this->patient])
            ->call('openItemForm')
            ->set('item.procedure_id', $this->exam->id)
            ->set('item.tooth_number', 36)
            ->call('addItem')
            ->assertHasNoErrors();

        $this->assertNull(TreatmentItem::sole()->tooth_number);
    }

    public function test_the_lifecycle_records_an_event_for_every_change(): void
    {
        $item = $this->plannedItem();

        $component = Livewire::test('pages::clinic.patients.treatments', ['patient' => $this->patient])
            ->call('changeStatus', $item->id, 'in_progress')
            ->call('changeStatus', $item->id, 'completed');

        $item->refresh();
        $this->assertSame(TreatmentStatus::Completed, $item->status);
        $this->assertNotNull($item->started_at);
        $this->assertNotNull($item->completed_at);
        $this->assertSame(
            [[null, 'planned'], ['planned', 'in_progress'], ['in_progress', 'completed']],
            $item->events()->orderBy('id')->get()->map(fn (TreatmentEvent $e) => [$e->from_status?->value, $e->to_status?->value])->all(),
        );

        // Completed is final.
        $component->call('changeStatus', $item->id, 'in_progress')->assertForbidden();
    }

    public function test_cancelling_needs_a_reason_and_keeps_the_item(): void
    {
        $item = $this->plannedItem();

        Livewire::test('pages::clinic.patients.treatments', ['patient' => $this->patient])
            ->call('changeStatus', $item->id, 'cancelled')
            ->assertStatus(422);

        Livewire::test('pages::clinic.patients.treatments', ['patient' => $this->patient])
            ->call('startCancel', $item->id)
            ->call('confirmCancel')
            ->assertHasErrors(['cancelReason'])
            ->set('cancelReason', 'Patient chose extraction instead')
            ->call('confirmCancel')
            ->assertHasNoErrors();

        $item->refresh();
        $this->assertSame(TreatmentStatus::Cancelled, $item->status);
        $event = TreatmentEvent::where('treatment_item_id', $item->id)->orderByDesc('id')->firstOrFail();
        $this->assertSame(TreatmentStatus::Cancelled, $event->to_status);
        $this->assertSame('Patient chose extraction instead', $event->note);
        $this->assertModelExists($item);
    }

    public function test_the_action_rechecks_the_stored_status(): void
    {
        $item = $this->plannedItem();
        TreatmentItem::whereKey($item->id)->update(['status' => 'completed']);

        // $item still believes it is planned; the action must use the locked database row.
        $this->expectException(ValidationException::class);
        app(ChangeTreatmentStatus::class)->handle($item, TreatmentStatus::InProgress, $this->dentist);
    }

    public function test_a_plan_follows_its_items(): void
    {
        $component = Livewire::test('pages::clinic.patients.treatments', ['patient' => $this->patient])
            ->call('openPlanForm')
            ->set('plan.title', 'Restorative plan')
            ->call('addPlan')
            ->assertHasNoErrors();

        $plan = TreatmentPlan::sole();
        $this->assertSame(TreatmentPlanStatus::Draft, $plan->status);

        $first = $this->plannedItem(['treatment_plan_id' => $plan->id]);
        $second = $this->plannedItem(['treatment_plan_id' => $plan->id]);

        $component->call('changeStatus', $first->id, 'completed');
        $this->assertSame(TreatmentPlanStatus::Active, $plan->fresh()?->status);

        $component->call('startCancel', $second->id)->set('cancelReason', 'Not needed')->call('confirmCancel');
        $this->assertSame(TreatmentPlanStatus::Completed, $plan->fresh()?->status);
    }

    public function test_receptionists_cannot_open_treatments_and_other_dentists_cannot_change_them(): void
    {
        $item = $this->plannedItem();

        $this->actingAs(User::factory()->receptionist()->withTwoFactor()->create());
        $this->get(route('clinic.patients.treatment-plans', $this->patient))->assertForbidden();

        $this->actingAs(User::factory()->dentist()->withTwoFactor()->create());
        $this->get(route('clinic.patients.treatment-plans', $this->patient))->assertForbidden();
        Livewire::test('pages::clinic.patients.treatments', ['patient' => $this->patient])->assertForbidden();
        $this->assertSame(TreatmentStatus::Planned, $item->fresh()?->status);
    }

    public function test_items_of_other_patients_cannot_be_targeted(): void
    {
        $other = Patient::factory()->assignedTo($this->dentist)->create();
        $foreign = TreatmentItem::factory()->create(['patient_id' => $other->id, 'dentist_id' => $this->dentist->id]);

        Livewire::test('pages::clinic.patients.treatments', ['patient' => $this->patient])
            ->call('changeStatus', $foreign->id, 'completed')
            ->assertNotFound();
    }

    public function test_only_active_procedures_and_dentists_can_be_chosen(): void
    {
        $inactive = Procedure::factory()->create(['scope' => 'mouth', 'is_active' => false]);
        $receptionist = User::factory()->receptionist()->create();

        Livewire::test('pages::clinic.patients.treatments', ['patient' => $this->patient])
            ->call('openItemForm')
            ->set('item.procedure_id', $inactive->id)
            ->set('item.dentist_id', $receptionist->id)
            ->call('addItem')
            ->assertHasErrors(['item.procedure_id', 'item.dentist_id']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function plannedItem(array $overrides = []): TreatmentItem
    {
        $item = TreatmentItem::factory()->create([
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'procedure_id' => $this->exam->id,
            'tooth_number' => null,
            ...$overrides,
        ]);
        $item->events()->create(['type' => 'created', 'to_status' => 'planned', 'performed_by' => $this->dentist->id, 'occurred_at' => now()]);

        return $item;
    }
}
