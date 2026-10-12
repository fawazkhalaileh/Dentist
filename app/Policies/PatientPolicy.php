<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Patient;
use App\Models\User;
use App\Policies\Concerns\ChecksPatientAccess;

class PatientPolicy
{
    use ChecksPatientAccess;

    public function viewAny(User $user): bool
    {
        return $user->canAny([
            Permission::PatientsViewAll->value,
            Permission::PatientsViewAssigned->value,
        ]);
    }

    /**
     * Demographics and contact details.
     */
    public function view(User $user, Patient $patient): bool
    {
        return $this->canSeePatient($user, $patient) || $this->isOwnRecord($user, $patient);
    }

    /**
     * Staff-side clinical record: medical/dental history, notes, chart, documents.
     */
    public function viewClinical(User $user, Patient $patient): bool
    {
        return $this->canSeeClinical($user, $patient);
    }

    public function writeClinical(User $user, Patient $patient): bool
    {
        return $user->can(Permission::ClinicalWrite->value)
            && $this->canSeeClinical($user, $patient);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::PatientsCreate->value);
    }

    public function update(User $user, Patient $patient): bool
    {
        return $user->can(Permission::PatientsUpdate->value);
    }

    /**
     * Archive (soft delete). Patients are never hard-deleted.
     */
    public function delete(User $user, Patient $patient): bool
    {
        return $user->can(Permission::PatientsArchive->value);
    }

    public function restore(User $user, Patient $patient): bool
    {
        return $user->can(Permission::PatientsArchive->value);
    }

    public function forceDelete(User $user, Patient $patient): bool
    {
        return false;
    }
}
