<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ClinicNavigation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClinicDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('clinic.dashboard', [
            'roles' => $user->getRoleNames(),
            'navigation' => ClinicNavigation::for($user),
        ]);
    }
}
