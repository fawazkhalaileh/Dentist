<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Staff must confirm two-factor authentication before using the clinic area.
 * Applied server-side to every clinic route (config: clinic.security.require_staff_mfa).
 */
class EnsureStaffHasTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            config('clinic.security.require_staff_mfa')
            && $user instanceof User
            && $user->isStaff()
            && ! $user->hasConfirmedTwoFactor()
        ) {
            if ($request->expectsJson()) {
                abort(403, __('Two-factor authentication is required for staff accounts.'));
            }

            // The security page shows why, based on the account state (not a flash
            // message, which the password-confirmation step in between would consume).
            return redirect()->route('security.edit');
        }

        return $next($request);
    }
}
