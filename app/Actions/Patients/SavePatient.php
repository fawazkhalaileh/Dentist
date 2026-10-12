<?php

namespace App\Actions\Patients;

use App\Models\Patient;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;

/**
 * Creates or updates a patient's demographic record and care team.
 * The audit entry lists which fields changed, never their values.
 */
class SavePatient
{
    /**
     * @param  array<string, mixed>  $data  validated PatientForm payload
     * @param  list<int>  $dentistIds
     */
    public function handle(array $data, array $dentistIds, User $actor, ?Patient $patient = null): Patient
    {
        return DB::transaction(function () use ($data, $dentistIds, $actor, $patient) {
            $isNew = $patient === null;
            $patient ??= new Patient(['created_by' => $actor->id]);

            $patient->fill($data);
            $changed = array_keys($patient->getDirty());
            $patient->save();

            $previousDentists = $isNew ? [] : $patient->dentists()->pluck('users.id')->all();
            $patient->dentists()->sync($dentistIds);
            $careTeamChanged = array_diff($previousDentists, $dentistIds) !== [] || array_diff($dentistIds, $previousDentists) !== [];

            if ($isNew) {
                Audit::record('patient.created', $patient);
            } elseif ($changed !== [] || $careTeamChanged) {
                Audit::record('patient.updated', $patient, array_filter([
                    'fields' => implode(',', array_diff($changed, ['updated_at'])) ?: null,
                    'care_team_changed' => $careTeamChanged ?: null,
                ]));
            }

            return $patient;
        });
    }
}
