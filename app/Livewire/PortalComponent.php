<?php

namespace App\Livewire;

use App\Enums\Permission;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Base class for patient portal pages. The patient record always comes from the
 * signed-in account, never from request input, so one patient can never reach
 * another's data. Re-checked on every Livewire request.
 */
abstract class PortalComponent extends Component
{
    public function boot(): void
    {
        Gate::authorize(Permission::PortalAccess->value);
    }

    protected function account(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    protected function patient(): ?Patient
    {
        return $this->account()->patient;
    }
}
