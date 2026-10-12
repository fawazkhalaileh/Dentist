<?php

namespace App\Actions\Patients;

use App\Models\ClinicalNote;
use App\Models\Patient;
use App\Models\User;
use App\Support\Audit;

/**
 * Adds a clinical note, or a correction of an earlier note. Notes are never
 * edited or deleted; a correction is a new note pointing at the one it replaces.
 */
class RecordClinicalNote
{
    public function handle(Patient $patient, string $body, User $author, ?ClinicalNote $amends = null): ClinicalNote
    {
        abort_if($amends !== null && $amends->patient_id !== $patient->id, 422);

        $note = $patient->clinicalNotes()->create([
            'author_id' => $author->id,
            'amends_id' => $amends?->id,
            'body' => trim($body),
        ]);

        Audit::record($amends ? 'clinical.note_amended' : 'clinical.note_created', $note, array_filter(['amends_id' => $amends?->id]));

        return $note;
    }
}
