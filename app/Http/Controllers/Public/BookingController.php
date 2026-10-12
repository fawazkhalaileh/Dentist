<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppointmentRequest;
use App\Models\AppointmentRequest;
use App\Models\Service;
use App\Models\StaffProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

/**
 * Public appointment requests. A request is not a booking: reception calls the
 * visitor back and books a time, so nothing here reveals the clinic's calendar.
 */
class BookingController extends Controller
{
    public function show(Request $request): View
    {
        return view('public.booking', [
            'dentists' => StaffProfile::shownPublicly()->translatedIn()->with('translations')->orderBy('sort_order')->get(),
            'services' => Service::published()->translatedIn()->with('translations')->orderBy('sort_order')->get(),
            'selectedService' => $request->integer('service') ?: null,
            'selectedDentist' => $request->integer('dentist') ?: null,
        ]);
    }

    public function store(StoreAppointmentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        AppointmentRequest::create([
            'name' => trim($data['name']),
            'phone' => trim($data['phone']),
            'email' => $data['email'] ?? null,
            'preferred_dentist_id' => $data['dentist'] ?? null,
            'service_id' => $data['service'] ?? null,
            'preferred_date' => $data['preferred_date'] ?? null,
            'preferred_time' => $data['preferred_time'],
            'message' => isset($data['message']) && trim($data['message']) !== '' ? trim($data['message']) : null,
            'locale' => App::getLocale(),
        ]);

        return redirect()->route('public.booking')->with('status', 'booking-requested');
    }
}
