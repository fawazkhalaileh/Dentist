<?php

namespace App\Actions\Patients;

use App\Enums\Role;
use App\Models\Patient;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Connects a self-registered portal account to a patient record, after staff
 * have checked the person's identity. One account per patient and vice versa.
 */
class LinkPortalAccount
{
    public function link(Patient $patient, string $email): User
    {
        return DB::transaction(function () use ($patient, $email) {
            $user = User::where('email', strtolower(trim($email)))->lockForUpdate()->first();

            if ($user === null || ! $user->hasRoleEnum(Role::Patient) || $user->isStaff()) {
                throw ValidationException::withMessages(['portalEmail' => __('No patient portal account uses this email. Ask the patient to register on the website first.')]);
            }
            if (Patient::where('user_id', $user->id)->whereKeyNot($patient->id)->exists()) {
                throw ValidationException::withMessages(['portalEmail' => __('This portal account is already linked to another patient record.')]);
            }

            $patient->user_id = $user->id;
            $patient->save();
            Audit::record('patient.portal_linked', $patient);

            return $user;
        });
    }

    public function unlink(Patient $patient): void
    {
        $patient->user_id = null;
        $patient->save();
        Audit::record('patient.portal_unlinked', $patient);
    }
}
