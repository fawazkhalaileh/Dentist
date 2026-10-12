<?php

namespace App\Actions\Treatments;

use App\Enums\TreatmentEventType;
use App\Enums\TreatmentStatus;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\TreatmentItem;
use App\Models\User;
use App\Support\Audit;
use App\Support\Fdi;
use Illuminate\Support\Facades\DB;

/**
 * Adds a planned treatment item and its "created" event in one transaction.
 */
class AddTreatmentItem
{
    /**
     * @param  array{procedure_id: int, tooth_number: ?int, surfaces: list<string>, dentist_id: int,
     *     treatment_plan_id: ?int, planned_for: ?string, notes: ?string}  $data  validated
     */
    public function handle(Patient $patient, array $data, User $actor): TreatmentItem
    {
        $procedure = Procedure::findOrFail($data['procedure_id']);
        $tooth = $data['tooth_number'];

        // Invariants the form validates too; re-checked here for any other caller.
        abort_if($procedure->scope !== 'mouth' && $tooth === null, 422, 'This procedure needs a tooth.');
        abort_if($tooth !== null && ! Fdi::isValid($tooth), 422);
        abort_if($procedure->scope === 'surface' && $data['surfaces'] === [], 422, 'Choose at least one surface.');
        $allowed = $tooth !== null ? array_map(fn ($s) => $s->value, Fdi::surfaces($tooth)) : [];
        abort_if(array_diff($data['surfaces'], $allowed) !== [], 422);

        return DB::transaction(function () use ($patient, $data, $actor, $procedure, $tooth) {
            $item = $patient->treatmentItems()->create([
                'treatment_plan_id' => $data['treatment_plan_id'],
                'tooth_number' => $procedure->scope === 'mouth' ? null : $tooth,
                'procedure_id' => $procedure->id,
                'dentist_id' => $data['dentist_id'],
                'status' => TreatmentStatus::Planned,
                'planned_for' => $data['planned_for'],
                'notes' => $data['notes'],
                'created_by' => $actor->id,
            ]);

            foreach ($procedure->scope === 'surface' ? array_unique($data['surfaces']) : [] as $surface) {
                $item->surfaces()->create(['surface' => $surface]);
            }

            $item->events()->create([
                'type' => TreatmentEventType::Created,
                'to_status' => TreatmentStatus::Planned,
                'performed_by' => $actor->id,
                'occurred_at' => now(),
            ]);

            Audit::record('treatment.item_created', $item, ['procedure' => $procedure->code, 'tooth' => $item->tooth_number]);

            return $item;
        });
    }
}
