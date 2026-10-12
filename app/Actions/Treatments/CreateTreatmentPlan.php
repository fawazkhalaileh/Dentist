<?php

namespace App\Actions\Treatments;

use App\Enums\TreatmentPlanStatus;
use App\Models\Patient;
use App\Models\TreatmentPlan;
use App\Models\User;
use App\Support\Audit;

/**
 * A named group of treatment items (e.g. "Restorative plan, spring"). Its status
 * follows its items: see ChangeTreatmentStatus::updatePlan().
 */
class CreateTreatmentPlan
{
    public function handle(Patient $patient, string $title, int $dentistId, ?string $notes, User $actor): TreatmentPlan
    {
        $plan = $patient->treatmentPlans()->create([
            'dentist_id' => $dentistId,
            'title' => trim($title),
            'status' => TreatmentPlanStatus::Draft,
            'notes' => $notes,
            'created_by' => $actor->id,
        ]);

        Audit::record('treatment.plan_created', $plan);

        return $plan;
    }
}
