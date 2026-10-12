<?php

namespace App\Actions\Patients;

use App\Models\DentalHistory;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\User;
use App\Support\Audit;

/**
 * Records a reviewed medical or dental history as a new version. Earlier
 * versions stay unchanged, so the record shows what was known and when.
 */
class RecordHistory
{
    /**
     * @param  array{allergies: ?string, no_known_allergies: bool, medications: ?string, conditions: ?string, notes: ?string}  $data
     */
    public function medical(Patient $patient, array $data, User $actor): MedicalHistory
    {
        $history = $patient->medicalHistories()->create([...$data, 'recorded_by' => $actor->id]);
        Audit::record('clinical.medical_history_recorded', $history);

        return $history;
    }

    /**
     * @param  array{chief_complaint: ?string, previous_treatments: ?string, notes: ?string}  $data
     */
    public function dental(Patient $patient, array $data, User $actor): DentalHistory
    {
        $history = $patient->dentalHistories()->create([...$data, 'recorded_by' => $actor->id]);
        Audit::record('clinical.dental_history_recorded', $history);

        return $history;
    }
}
