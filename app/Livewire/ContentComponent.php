<?php

namespace App\Livewire;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Base class for CMS components. Livewire runs boot() on the initial render
 * and on every later action request, so the content permission is re-checked
 * server-side for each save/delete, not only when the page first loads.
 */
abstract class ContentComponent extends Component
{
    public function boot(): void
    {
        Gate::authorize(Permission::ContentManage->value);
    }

    protected function actor(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
