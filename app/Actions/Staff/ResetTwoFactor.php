<?php

namespace App\Actions\Staff;

use App\Models\User;
use App\Support\Audit;

/**
 * Clears a staff member's authenticator (e.g. a lost phone). They must set up
 * two-factor authentication again before the clinic dashboard reopens.
 */
class ResetTwoFactor
{
    public function handle(User $user, User $actor): void
    {
        abort_if($user->is($actor), 422);

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        Audit::record('staff.two_factor_reset', $user);
    }
}
