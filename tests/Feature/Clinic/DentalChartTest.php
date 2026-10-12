<?php

namespace Tests\Feature\Clinic;

use App\Enums\TreatmentStatus;
use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\TreatmentItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DentalChartTest extends TestCase
{
    use RefreshDatabase;

    private User $dentist;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dentist = User::factory()->dentist()->withTwoFactor()->create();
        $this->patient = Patient::factory()->assignedTo($this->dentist)->create();
        $this->actingAs($this->dentist);
    }

    public function test_the_chart_shows_all_permanent_teeth_in_fdi_order_and_links_each_to_its_treatments(): void
    {
        $response = $this->get(route('clinic.patients.chart', $this->patient))->assertOk();

        foreach ([18, 11, 21, 28, 48, 41, 31, 38] as $tooth) {
            $response->assertSee('aria-label="Tooth '.$tooth.',', escape: false)
                ->assertSee(route('clinic.patients.treatment-plans', ['patient' => $this->patient, 'tooth' => $tooth]), escape: false);
        }
        $response->assertSeeInOrder(['Tooth 18,', 'Tooth 11,', 'Tooth 21,', 'Tooth 28,', 'Tooth 48,', 'Tooth 41,', 'Tooth 31,', 'Tooth 38,'], escape: false);
    }

    public function test_the_primary_chart_shows_childrens_teeth(): void
    {
        Livewire::withQueryParams(['dentition' => 'primary'])
            ->test('pages::clinic.patients.chart', ['patient' => $this->patient])
            ->assertSee('Tooth 55,', escape: false)
            ->assertSee('Tooth 75,', escape: false)
            ->assertDontSee('Tooth 18,', escape: false);
    }

    public function test_treatments_are_described_in_text_as_well_as_colour(): void
    {
        $this->item(36, ['O', 'M'], TreatmentStatus::Planned, 'Filling');
        $this->item(26, [], TreatmentStatus::Completed, 'Crown', 'tooth');
        $this->item(11, [], TreatmentStatus::Cancelled, 'Extraction', 'tooth');

        $this->get(route('clinic.patients.chart', $this->patient))
            ->assertOk()
            ->assertSee('Tooth 36, Lower left first molar, 1 planned', escape: false)
            ->assertSee('Filling (MO) · Planned')
            ->assertSee('Crown · Completed')
            // Cancelled treatments are not drawn or listed on the chart.
            ->assertSee('Tooth 11, Upper right central incisor, no treatments', escape: false)
            ->assertDontSee('Extraction');
    }

    public function test_the_tooth_filter_on_the_treatments_page_shows_only_that_tooth(): void
    {
        $this->item(36, ['O'], TreatmentStatus::Planned, 'Filling');
        $this->item(46, ['O'], TreatmentStatus::Planned, 'Sealant');

        Livewire::withQueryParams(['tooth' => 36])
            ->test('pages::clinic.patients.treatments', ['patient' => $this->patient])
            ->assertSee('Filling')
            ->assertDontSee('Sealant')
            ->assertSee('Lower left first molar');
    }

    public function test_opening_the_chart_is_audited_and_receptionists_cannot_open_it(): void
    {
        $this->get(route('clinic.patients.chart', $this->patient))->assertOk();
        $this->assertTrue(AuditLog::where('action', 'clinical.chart_viewed')->where('patient_id', $this->patient->id)->exists());

        $this->actingAs(User::factory()->receptionist()->withTwoFactor()->create());
        $this->get(route('clinic.patients.chart', $this->patient))->assertForbidden();
    }

    /**
     * @param  list<string>  $surfaces
     */
    private function item(int $tooth, array $surfaces, TreatmentStatus $status, string $name, string $scope = 'surface'): void
    {
        $procedure = Procedure::factory()->create(['name' => $name, 'scope' => $scope]);
        $item = TreatmentItem::factory()->create([
            'patient_id' => $this->patient->id,
            'dentist_id' => $this->dentist->id,
            'procedure_id' => $procedure->id,
            'tooth_number' => $tooth,
            'status' => $status,
        ]);
        foreach ($surfaces as $surface) {
            $item->surfaces()->create(['surface' => $surface]);
        }
    }
}
