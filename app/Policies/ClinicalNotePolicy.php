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
