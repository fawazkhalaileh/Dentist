<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\ClinicalNote;
use App\Models\Patient;
use App\Models\User;
use App\Policies\Concerns\ChecksPatientAccess;

class ClinicalNotePolicy
{
    use ChecksPatientAccess;

    public function view(User $user, ClinicalNote $note): bool
    {
        return $this->canSeeClinical($user, $note->patient);
    }

    public function create(User $user, Patient $patient): bool
    {
        return $user->can(Permission::ClinicalWrite->value)
            && $this->canSeeClinical($user, $patient);
    }

    /**
     * Correct a note by adding an amending note. Only the author (or someone who
     * sees all clinical records, i.e. an administrator) may correct it, and only
     * its latest version, so a correction chain never forks.
     */
    public function amend(User $user, ClinicalNote $note): bool
    {
        return $this->create($user, $note->patient)
            && ($note->author_id === $user->id || $user->can(Permission::ClinicalViewAll->value))
            && ! ClinicalNote::where('amends_id', $note->id)->exists();
    }

    /**
     * Notes are never edited; corrections are new amending notes.
     */
    public function update(User $user, ClinicalNote $note): bool
    {
        return false;
    }

    public function delete(User $user, ClinicalNote $note): bool
    {
        return false;
    }
}
