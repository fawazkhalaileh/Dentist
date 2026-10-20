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

class ChartDrawingTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_kind_of_whole_tooth_treatment_is_drawn_on_the_real_tooth_images(): void
    {
        $dentist = User::factory()->dentist()->withTwoFactor()->create();
        $patient = Patient::factory()->assignedTo($dentist)->create();
        $this->actingAs($dentist);

        foreach ([[16, 'CROWN', 'prosthodontic', TreatmentStatus::Completed], [21, 'RCT', 'endodontic', TreatmentStatus::InProgress],
            [48, 'EXT', 'surgical', TreatmentStatus::Completed], [37, 'IMPL', 'surgical', TreatmentStatus::Completed],
            [47, 'IMPL', 'surgical', TreatmentStatus::Planned], [25, 'SEAL', 'preventive', TreatmentStatus::Planned]] as [$tooth, $code, $category, $status]) {
            TreatmentItem::factory()->create([
                'patient_id' => $patient->id, 'dentist_id' => $dentist->id, 'tooth_number' => $tooth, 'status' => $status,
                'procedure_id' => Procedure::firstOrCreate(['code' => $code], Procedure::factory()->raw(['code' => $code, 'category' => $category, 'scope' => 'tooth']))->id,
            ]);
        }

        Livewire::test('pages::clinic.patients.chart', ['patient' => $patient, 'embedded' => true])
            ->assertSee('images/teeth/16-side.webp', escape: false)
            ->assertSee('images/teeth/16-top.webp', escape: false)
            ->assertSee('mask="url(#crown-16)"', escape: false)
            ->assertSee('mask="url(#root-21)"', escape: false)
            ->assertSee('mask="url(#root-37)"', escape: false);

        Livewire::test('pages::clinic.patients.chart', ['patient' => $patient, 'embedded' => true])
            ->set('dentition', 'primary')
            ->assertSee('images/teeth/11-side.webp', escape: false)
            ->assertDontSee('images/teeth/51-side.webp', escape: false);
    }
}
