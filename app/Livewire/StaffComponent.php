<?php

namespace App\Livewire;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Base class for staff management screens (administrators only). Like
 * ContentComponent, boot() re-checks the permission on every Livewire request.
 */
abstract class StaffComponent extends Component
{
    public function boot(): void
    {
        Gate::authorize(Permission::StaffManage->value);
    }

    protected function actor(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
