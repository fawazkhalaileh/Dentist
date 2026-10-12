<?php

namespace App\Policies\Concerns;

use App\Enums\Permission;
use App\Models\Patient;
use App\Models\User;

trait ChecksPatientAccess
{
    /**
     * Staff access to a patient's demographic record.
     */
    protected function canSeePatient(User $user, Patient $patient): bool
    {
        if ($user->can(Permission::PatientsViewAll->value)) {
            return true;
        }

        return $user->can(Permission::PatientsViewAssigned->value)
            && $patient->hasCareTeamMember($user);
    }

    /**
     * Staff access to clinical records (histories, notes, chart, documents).
     * Receptionists have no clinical permission and never pass this check.
     */
    protected function canSeeClinical(User $user, Patient $patient): bool
    {
        if ($user->can(Permission::ClinicalViewAll->value)) {
            return true;
        }

        return $user->can(Permission::ClinicalViewAssigned->value)
            && $patient->hasCareTeamMember($user);
    }

    protected function isOwnRecord(User $user, Patient $patient): bool
    {
        return $patient->user_id !== null
            && $patient->user_id === $user->id
            && $user->can(Permission::PortalAccess->value);
    }
}
