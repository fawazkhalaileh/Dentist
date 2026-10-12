<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Patient;
use App\Models\TreatmentItem;
use App\Models\User;
use App\Policies\Concerns\ChecksPatientAccess;

class TreatmentItemPolicy
{
    use ChecksPatientAccess;

    public function view(User $user, TreatmentItem $item): bool
    {
        return $this->canSeeClinical($user, $item->patient)
            || $this->isOwnRecord($user, $item->patient);
    }

    public function create(User $user, Patient $patient): bool
    {
        return $user->can(Permission::TreatmentsManage->value)
            && $this->canSeeClinical($user, $patient);
    }

    public function changeStatus(User $user, TreatmentItem $item): bool
    {
        return $user->can(Permission::TreatmentsManage->value)
            && $this->canSeeClinical($user, $item->patient)
            && ! $item->status->isFinal();
    }

    /**
     * Treatment items are cancelled through a status change, never deleted.
     */
    public function delete(User $user, TreatmentItem $item): bool
    {
        return false;
    }

    public function forceDelete(User $user, TreatmentItem $item): bool
    {
        return false;
    }
}
