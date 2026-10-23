<?php

namespace Tests\Feature\Clinic;

use App\Actions\Treatments\ChangeTreatmentStatus;
use App\Enums\ToothSurface;
use App\Enums\TreatmentStatus;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\TreatmentItem;
use App\Models\TreatmentPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class TreatmentWorkflowEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    private User $dentist;

    private Patient $patient;

    private Procedure $filling;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dentist = User::factory()->dentist()->withTwoFactor()->create();
        $this->patient = Patient::factory()->assignedTo($this->dentist)->create();
        $this->filling = Procedure::factory()->create(['name' => 'Primary filling', 'scope' => 'surface']);
        $this->actingAs($this->dentist);
    }

    public function test_a_primary_surface_filling_is_saved_and_drawn_on_the_primary_chart(): void
    {
        $chart = Livewire::test('pages::clinic.patients.chart', ['patient' => $this->patient, 'embedded' => true])
            ->set('dentition', 'primary');

        Livewire::test('pages::clinic.patients.treatments', ['patient' => $this->patient])
            ->call('openItemForm')
            ->set('item.procedure_id', $this->filling->id)
            ->set('item.tooth_number', 75)
            ->set('item.surfaces', ['O', 'D'])
            ->call('addItem')
            ->assertHasNoErrors()
            ->assertDispatched('treatments-changed');

        $item = TreatmentItem::with('surfaces')->sole();
        $this->assertSame(75, $item->tooth_number);
        $this->assertSame(TreatmentStatus::Planned, $item->status);
        $this->assertEqualsCanonicalizing(['O', 'D'], $item->surfaces->map(fn ($surface) => $surface->surface->value)->all());

        $chart->dispatch('treatments-changed')
            ->assertSee('Tooth 75, Lower left second molar, Primary filling (OD) · Planned', escape: false);
        $this->assertSame(TreatmentStatus::Planned, $chart->get('teeth')[75]['surfaces']['O']['status']);
        $this->assertSame(TreatmentStatus::Planned, $chart->get('teeth')[75]['surfaces']['D']['status']);
    }

    public function test_incisors_and_canines_offer_incisal_while_molars_offer_occlusal(): void
    {
        $component = Livewire::test('pages::clinic.patients.treatments', ['patient' => $this->patient])
            ->call('openItemForm')
            ->set('item.procedure_id', $this->filling->id);

        foreach ([11, 13, 36] as $tooth) {
            $component->set('item.tooth_number', $tooth);
            $surfaces = $component->get('availableSurfaces');
            $this->assertContains($tooth === 36 ? ToothSurface::Occlusal : ToothSurface::Incisal, $surfaces);
            $this->assertNotContains($tooth === 36 ? ToothSurface::Incisal : ToothSurface::Occlusal, $surfaces);
        }
    }

    public function test_changing_the_tooth_clears_previously_selected_surfaces(): void
    {
        Livewire::test('pages::clinic.patients.treatments', ['patient' => $this->patient])
            ->call('openItemForm')
            ->set('item.procedure_id', $this->filling->id)
            ->set('item.tooth_number', 36)
            ->set('item.surfaces', ['O', 'M'])
            ->set('item.tooth_number', 11)
            ->assertSet('item.surfaces', [])
            ->call('addItem')
            ->assertHasErrors(['item.surfaces'])
            ->set('item.surfaces', ['I'])
            ->call('addItem')
            ->assertHasNoErrors();

        $this->assertSame(['I'], TreatmentItem::sole()->surfaces->map(fn ($surface) => $surface->surface->value)->all());
    }

    public function test_changing_to_a_whole_tooth_procedure_drops_surface_selections(): void
    {
        $crown = Procedure::factory()->create(['scope' => 'tooth']);
        $component = Livewire::test('pages::clinic.patients.treatments', ['patient' => $this->patient])
            ->call('openItemForm')
            ->set('item.procedure_id', $this->filling->id)
            ->set('item.tooth_number', 36)
            ->set('item.surfaces', ['O', 'D'])
            ->set('item.procedure_id', $crown->id);

        $component->assertSet('item.surfaces', []);
    }

    public function test_final_statuses_reject_further_changes_without_recording_events(): void
    {
        foreach ([TreatmentStatus::Completed, TreatmentStatus::Cancelled] as $from) {
            foreach (TreatmentStatus::cases() as $to) {
                $item = TreatmentItem::factory()->create([
                    'patient_id' => $this->patient->id,
                    'dentist_id' => $this->dentist->id,
                    'status' => $from,
                ]);
                $before = $item->fresh()?->getRawOriginal();

                try {
                    app(ChangeTreatmentStatus::class)->handle($item, $to, $this->dentist, 'Cancellation reason');
                    $this->fail("A {$from->value} treatment must not transition to {$to->value}.");
                } catch (ValidationException $exception) {
                    $this->assertArrayHasKey('status', $exception->errors());
                }

                $this->assertSame($before, $item->fresh()?->getRawOriginal());
                $this->assertSame(0, $item->events()->count());
            }
        }
    }

    public function test_a_completed_item_cannot_be_replanned_and_a_cancelled_item_cannot_be_completed_through_livewire(): void
    {
        foreach ([[TreatmentStatus::Completed, 'planned'], [TreatmentStatus::Cancelled, 'completed']] as [$from, $to]) {
            $item = TreatmentItem::factory()->create([
                'patient_id' => $this->patient->id,
                'dentist_id' => $this->dentist->id,
                'status' => $from,
            ]);

            Livewire::test('pages::clinic.patients.treatments', ['patient' => $this->patient])
                ->call('changeStatus', $item->id, $to)
                ->assertForbidden();

            $this->assertSame($from, $item->fresh()?->status);
            $this->assertSame(0, $item->events()->count());
        }
    }

    public function test_named_plan_totals_follow_items_added_completed_and_cancelled_through_the_form(): void
    {
        $exam = Procedure::factory()->create(['scope' => 'mouth']);
        $component = Livewire::test('pages::clinic.patients.treatments', ['patient' => $this->patient])
            ->call('openPlanForm')
            ->set('plan.title', 'Child restorative care')
            ->call('addPlan')
            ->assertHasNoErrors();
        $plan = TreatmentPlan::sole();

        foreach (['20', '30', '40'] as $price) {
            $component->call('openItemForm')
                ->set('item.procedure_id', $exam->id)
                ->set('item.treatment_plan_id', $plan->id)
                ->set('item.price', $price)
                ->call('addItem')
                ->assertHasNoErrors();
        }

        $items = $plan->items()->orderBy('id')->get();
        $this->assertCount(3, $items);
        $component->assertSee('Child restorative care')
            ->assertSeeInOrder(['of which still to do: 90 JD', '90 JD'])
            ->call('changeStatus', $items[0]->id, 'completed')
            ->assertHasNoErrors()
            ->assertSeeInOrder(['of which still to do: 70 JD', '90 JD'])
            ->call('startCancel', $items[1]->id)
            ->set('cancelReason', 'No longer required')
            ->call('confirmCancel')
            ->assertHasNoErrors()
            ->assertSeeInOrder(['of which still to do: 40 JD', '60 JD']);
    }

    public function test_chart_selection_filters_actual_rows_and_show_all_restores_them(): void
    {
        foreach ([36 => 'Selected tooth work', 46 => 'Other tooth work'] as $tooth => $name) {
            TreatmentItem::factory()->create([
                'patient_id' => $this->patient->id,
                'dentist_id' => $this->dentist->id,
                'procedure_id' => Procedure::factory()->create(['name' => $name, 'scope' => 'tooth'])->id,
                'tooth_number' => $tooth,
            ]);
        }

        $chart = Livewire::test('pages::clinic.patients.chart', ['patient' => $this->patient, 'embedded' => true])
            ->call('selectTooth', 36)
            ->assertDispatched('tooth-selected', tooth: 36);

        // Livewire's component harness does not relay events between separate test instances.
        Livewire::test('pages::clinic.patients.treatments', ['patient' => $this->patient, 'embedded' => true])
            ->dispatch('tooth-selected', tooth: $chart->get('selected'))
            ->assertSee('Selected tooth work')
            ->assertDontSee('Other tooth work')
            ->call('showAllTeeth')
            ->assertSet('tooth', null)
            ->assertDispatched('tooth-cleared')
            ->assertSee('Selected tooth work')
            ->assertSee('Other tooth work');

        $chart->dispatch('tooth-cleared')->assertSet('selected', null);
    }

    public function test_an_arabic_dentist_can_open_the_tab_validate_price_and_add_treatment(): void
    {
        $this->dentist->update(['locale' => 'ar']);
        $this->get(route('clinic.patients.show', ['patient' => $this->patient, 'tab' => 'treatments']))
            ->assertOk()
            ->assertSee('dir="rtl"', escape: false);
        $this->assertSame('ar', app()->getLocale());

        Livewire::test('pages::clinic.patients.treatments', ['patient' => $this->patient, 'embedded' => true])
            ->call('openItemForm')
            ->set('item.procedure_id', $this->filling->id)
            ->set('item.tooth_number', 75)
            ->set('item.surfaces', ['O'])
            ->set('item.price', 'free')
            ->call('addItem')
            ->assertHasErrors(['item.price'])
            ->assertSee('أدخل مبلغًا مثل 25 أو 25.500.')
            ->set('item.price', '25')
            ->call('addItem')
            ->assertHasNoErrors();

        $this->assertSame(25000, TreatmentItem::sole()->price);
    }

    public function test_add_item_rechecks_care_team_membership_after_the_component_was_opened(): void
    {
        $component = Livewire::test('pages::clinic.patients.treatments', ['patient' => $this->patient])
            ->call('openItemForm')
            ->set('item.procedure_id', $this->filling->id)
            ->set('item.tooth_number', 36)
            ->set('item.surfaces', ['O']);

        $this->patient->dentists()->detach($this->dentist);

        $component->call('addItem')->assertForbidden();

        $this->assertDatabaseCount('treatment_items', 0);
        $this->assertDatabaseCount('treatment_events', 0);
    }
}
