<?php

namespace App\Http\Controllers\Portal;

use App\Enums\AppointmentStatus;
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
        $patient = $user->patient;

        return view('portal.dashboard', [
            'patient' => $patient,
            'next' => $patient?->appointments()
                ->with(['dentist:id,name', 'service.translations'])
                ->where('starts_at', '>=', now())
                ->whereIn('status', array_map(fn (AppointmentStatus $s) => $s->value, [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed]))
                ->orderBy('starts_at')
                ->first(),
        ]);
    }
}
