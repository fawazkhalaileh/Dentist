<?php

namespace App\Listeners;

use App\Models\User;
use App\Support\Audit;
use Illuminate\Auth\Events\Login;

class RecordSuccessfulLogin
{
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $event->user->forceFill(['last_login_at' => now()])->saveQuietly();

        Audit::record('auth.login', $event->user, userId: $event->user->id);
    }
}
