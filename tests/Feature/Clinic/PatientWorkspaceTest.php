<?php

namespace Tests\Feature\Clinic;

use App\Enums\TreatmentStatus;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\TreatmentItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PatientWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private User $dentist;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dentist = User::factory()->dentist()->withTwoFactor()->create();
        $this->patient = Patient::factory()->assignedTo($this->dentist)->create(['first_name' => 'Tab', 'last_name' => 'Patient']);
        $this->actingAs($this->dentist);
    }

    public function test_every_tab_opens_inside_the_patient_page(): void
    {
        foreach (['overview', 'treatments', 'history', 'notes', 'documents', 'appointments'] as $tab) {
            $this->get(route('clinic.patients.show', ['patient' => $this->patient, 'tab' => $tab]))
                ->assertOk()
                ->assertSee('Tab Patient')
                ->assertSee('role="tablist"', escape: false)
                ->assertDontSee('Back to patient');
        }
    }

    public function test_receptionists_only_get_non_clinical_tabs(): void
    {
        $this->actingAs(User::factory()->receptionist()->withTwoFactor()->create());

        Livewire::test('pages::clinic.patients.show', ['patient' => $this->patient])
            ->assertSee('Overview')
            ->assertSee('Appointments')
            ->assertDontSee('Chart &amp; treatments', escape: false)
            ->assertDontSee('Treatments done')
            ->set('tab', 'notes')
            ->assertSet('tab', 'overview');
    }

    public function test_clicking_a_tooth_on_the_overview_opens_its_treatments(): void
    {
        $component = Livewire::test('pages::clinic.patients.show', ['patient' => $this->patient])
            ->assertSet('tab', 'overview')
            ->dispatch('tooth-selected', tooth: 36)
            ->assertSet('tab', 'treatments')
            ->assertSet('tooth', 36);

        $component->dispatch('tooth-cleared')->assertSet('tooth', null);
    }

    public function test_the_overview_dashboard_summarises_treatments(): void
    {
        $rct = Procedure::factory()->create(['name' => 'Root canal treatment', 'scope' => 'tooth', 'color' => '#16a34a']);
        TreatmentItem::factory()->create(['patient_id' => $this->patient->id, 'procedure_id' => $rct->id, 'tooth_number' => 36, 'status' => TreatmentStatus::Completed, 'completed_at' => now()]);
        TreatmentItem::factory()->create(['patient_id' => $this->patient->id, 'procedure_id' => $rct->id, 'tooth_number' => 46, 'status' => TreatmentStatus::Planned]);

        Livewire::test('pages::clinic.patients.show', ['patient' => $this->patient])
            ->assertSeeInOrder(['Treatments done', '1', 'Planned / in progress', '1'])
            ->assertSee('Root canal treatment')
            ->assertSee('#16a34a', escape: false);
    }

    public function test_the_chart_colours_teeth_by_procedure_and_lightens_planned_work(): void
    {
        $rct = Procedure::factory()->create(['name' => 'Root canal treatment', 'scope' => 'tooth', 'color' => '#16a34a']);
        TreatmentItem::factory()->create(['patient_id' => $this->patient->id, 'procedure_id' => $rct->id, 'tooth_number' => 36, 'status' => TreatmentStatus::Planned]);

        Livewire::test('pages::clinic.patients.chart', ['patient' => $this->patient, 'embedded' => true])
            ->assertSee('stroke="#16a34a"', escape: false)
            ->assertSee('stroke-dasharray="3 2"', escape: false)
            ->assertSee('Root canal treatment')
            ->call('selectTooth', 36)
            ->assertDispatched('tooth-selected', tooth: 36);
    }

    public function test_the_treatments_tab_filters_by_the_selected_tooth_and_refreshes_the_chart(): void
    {
        $filling = Procedure::factory()->create(['name' => 'Filling', 'scope' => 'surface']);

        Livewire::test('pages::clinic.patients.treatments', ['patient' => $this->patient, 'embedded' => true])
            ->dispatch('tooth-selected', tooth: 36)
            ->assertSet('tooth', 36)
            ->call('openItemForm')
            ->assertSet('item.tooth_number', 36)
            ->set('item.procedure_id', $filling->id)
            ->set('item.surfaces', ['O'])
            ->call('addItem')
            ->assertHasNoErrors()
            ->assertDispatched('treatments-changed')
            ->call('showAllTeeth')
            ->assertSet('tooth', null)
            ->assertDispatched('tooth-cleared');
    }

    public function test_old_section_links_still_work(): void
    {
        $this->get(route('clinic.patients.chart', $this->patient))->assertOk();
        $this->get(route('clinic.patients.treatment-plans', ['patient' => $this->patient, 'tooth' => 36]))->assertOk()->assertSee('Lower left first molar');
    }
}
