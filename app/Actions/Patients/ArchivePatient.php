<?php

namespace App\Actions\Patients;

use App\Models\Patient;
use App\Support\Audit;

/**
 * Archiving hides a patient from lists and search. Nothing is deleted:
 * clinical history must be kept, and an archived patient can be restored.
 */
class ArchivePatient
{
    public function archive(Patient $patient): void
    {
        $patient->delete();
        Audit::record('patient.archived', $patient);
    }

    public function restore(Patient $patient): void
    {
        $patient->restore();
        Audit::record('patient.restored', $patient);
    }
}
