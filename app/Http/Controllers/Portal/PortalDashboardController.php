<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        // Only the record linked to this account; never looked up by request input.
        return view('portal.dashboard', [
            'patient' => $user->patient,
        ]);
    }
}
