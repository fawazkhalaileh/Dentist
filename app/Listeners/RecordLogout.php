<?php

namespace App\Listeners;

use App\Models\User;
use App\Support\Audit;
use Illuminate\Auth\Events\Logout;

class RecordLogout
{
    public function handle(Logout $event): void
    {
        if ($event->user instanceof User) {
            Audit::record('auth.logout', $event->user, userId: $event->user->id);
        }
    }
}
