<?php

namespace Database\Factories;

use App\Enums\TreatmentStatus;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\TreatmentItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Creates the item row only. Lifecycle events are written by the treatment
 * actions (Phase 4); tests that need history create events explicitly.
 *
 * @extends Factory<TreatmentItem>
 */
class TreatmentItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'tooth_number' => 11,
            'procedure_id' => Procedure::factory(),
            'dentist_id' => User::factory()->dentist(),
            'status' => TreatmentStatus::Planned,
            'created_by' => fn (array $attributes) => $attributes['dentist_id'],
        ];
    }
}
