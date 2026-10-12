<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Appointment;
use App\Models\User;
use App\Policies\Concerns\ChecksPatientAccess;

class AppointmentPolicy
{
    use ChecksPatientAccess;

    public function viewAny(User $user): bool
    {
        return $user->canAny([
            Permission::AppointmentsViewAll->value,
            Permission::AppointmentsViewOwn->value,
        ]);
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->can(Permission::AppointmentsViewAll->value)) {
            return true;
        }

        if ($user->can(Permission::AppointmentsViewOwn->value) && $appointment->dentist_id === $user->id) {
            return true;
        }

        return $this->isOwnRecord($user, $appointment->patient);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::AppointmentsManage->value);
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->can(Permission::AppointmentsManage->value);
    }

    /**
     * Staff can cancel any appointment; patients only their own future ones.
     */
    public function cancel(User $user, Appointment $appointment): bool
    {
        if ($user->can(Permission::AppointmentsManage->value)) {
            return true;
        }

        return $this->isOwnRecord($user, $appointment->patient)
            && $appointment->starts_at->isFuture();
    }

    /**
     * Appointments are cancelled, never deleted, to keep history.
     */
    public function delete(User $user, Appointment $appointment): bool
    {
        return false;
    }
}
