<?php

use App\Enums\Permission;
use App\Http\Controllers\Clinic\ClinicDashboardController;
use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Clinic dashboard (staff)
|--------------------------------------------------------------------------
| Every route checks authorisation on the server: area access, confirmed
| two-factor authentication, and a per-module permission or policy.
| Patient routes use PatientPolicy, which applies care-team rules for dentists.
*/

$can = fn (Permission $permission) => 'can:'.$permission->value;

Route::middleware(['auth', 'verified', 'active', $can(Permission::ClinicAccess), 'staff.mfa'])
    ->prefix('clinic')
    ->name('clinic.')
    ->group(function () use ($can) {
        Route::get('/', ClinicDashboardController::class)->name('dashboard');

        $placeholder = fn (string $title, int $phase) => ['title' => $title, 'phase' => $phase];

        // Patients
        Route::view('patients', 'clinic.placeholder', $placeholder('Patients', 3))
            ->middleware('can:viewAny,'.Patient::class)->name('patients.index');
        Route::view('patients/create', 'clinic.placeholder', $placeholder('New patient', 3))
            ->middleware('can:create,'.Patient::class)->name('patients.create');

        Route::prefix('patients/{patient}')->name('patients.')->group(function () use ($placeholder) {
            Route::view('/', 'clinic.placeholder', $placeholder('Patient overview', 3))
                ->middleware('can:view,patient')->name('show');
            Route::view('appointments', 'clinic.placeholder', $placeholder('Patient appointments', 5))
                ->middleware('can:view,patient')->name('appointments');
            Route::view('history', 'clinic.placeholder', $placeholder('Medical and dental history', 3))
                ->middleware('can:viewClinical,patient')->name('history');
            Route::view('chart', 'clinic.placeholder', $placeholder('Dental chart', 4))
                ->middleware('can:viewClinical,patient')->name('chart');
            Route::view('treatment-plans', 'clinic.placeholder', $placeholder('Treatment plans', 4))
                ->middleware('can:viewClinical,patient')->name('treatment-plans');
            Route::view('notes', 'clinic.placeholder', $placeholder('Clinical notes', 4))
                ->middleware('can:viewClinical,patient')->name('notes');
            Route::view('documents', 'clinic.placeholder', $placeholder('Documents', 4))
                ->middleware('can:viewClinical,patient')->name('documents');
        });

        // Scheduling
        Route::view('appointments', 'clinic.placeholder', $placeholder('Appointment calendar', 5))
            ->middleware('can:viewAny,'.Appointment::class)->name('appointments.index');
        Route::view('appointment-requests', 'clinic.placeholder', $placeholder('Appointment requests', 5))
            ->middleware($can(Permission::AppointmentRequestsManage))->name('appointment-requests.index');
        Route::view('schedules', 'clinic.placeholder', $placeholder('Working hours and closures', 5))
            ->middleware($can(Permission::SchedulesManage))->name('schedules.index');

        // Catalogue and content
        Route::view('procedures', 'clinic.placeholder', $placeholder('Procedure catalogue', 4))
            ->middleware($can(Permission::ProceduresManage))->name('procedures.index');

        Route::prefix('content')->name('content.')->middleware($can(Permission::ContentManage))->group(function () use ($placeholder) {
            Route::view('services', 'clinic.placeholder', $placeholder('Website services', 2))->name('services.index');
            Route::view('blog', 'clinic.placeholder', $placeholder('Blog', 2))->name('blog.index');
            Route::view('pages', 'clinic.placeholder', $placeholder('Website pages', 2))->name('pages.index');
            Route::view('media', 'clinic.placeholder', $placeholder('Media library', 2))->name('media.index');
        });

        // Administration
        Route::view('staff', 'clinic.placeholder', $placeholder('Dentists and staff', 3))
            ->middleware($can(Permission::StaffManage))->name('staff.index');
        Route::view('settings', 'clinic.placeholder', $placeholder('Clinic settings', 3))
            ->middleware($can(Permission::SettingsManage))->name('settings.index');
        Route::view('audit', 'clinic.placeholder', $placeholder('Audit log', 6))
            ->middleware($can(Permission::AuditView))->name('audit.index');
    });
