<?php

namespace App\Actions\Staff;

use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\Password;

/**
 * A password-setup link an administrator can hand to a new or locked-out staff
 * member (for example by message), so accounts work without outgoing email.
 * It is a normal password-reset link and expires like one (config/auth.php).
 */
class CreateSetupLink
{
    public function handle(User $user): string
    {
        abort_unless($user->is_active, 422);

        $token = Password::broker()->createToken($user);

        Audit::record('staff.setup_link_created', $user);

        return route('password.reset', ['token' => $token, 'email' => $user->email]);
    }
}
