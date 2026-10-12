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
        Route::livewire('patients', 'pages::clinic.patients.index')
            ->middleware('can:viewAny,'.Patient::class)->name('patients.index');
        Route::livewire('patients/create', 'pages::clinic.patients.edit')
            ->middleware('can:create,'.Patient::class)->name('patients.create');

        Route::prefix('patients/{patient}')->name('patients.')->group(function () use ($placeholder) {
            Route::livewire('/', 'pages::clinic.patients.show')
                ->middleware('can:view,patient')->name('show');
            Route::livewire('edit', 'pages::clinic.patients.edit')
                ->middleware('can:update,patient')->name('edit');
            Route::view('appointments', 'clinic.placeholder', $placeholder('Patient appointments', 5))
                ->middleware('can:view,patient')->name('appointments');
            Route::livewire('history', 'pages::clinic.patients.history')
                ->middleware('can:viewClinical,patient')->name('history');
            Route::view('chart', 'clinic.placeholder', $placeholder('Dental chart', 4))
                ->middleware('can:viewClinical,patient')->name('chart');
            Route::view('treatment-plans', 'clinic.placeholder', $placeholder('Treatment plans', 4))
                ->middleware('can:viewClinical,patient')->name('treatment-plans');
            Route::livewire('notes', 'pages::clinic.patients.notes')
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
        Route::livewire('procedures', 'pages::clinic.procedures.index')
            ->middleware($can(Permission::ProceduresManage))->name('procedures.index');

        // Website content (CMS). Components also re-check the permission on every action.
        Route::prefix('content')->name('content.')->middleware($can(Permission::ContentManage))->group(function () {
            Route::livewire('services', 'pages::clinic.content.services.index')->name('services.index');
            Route::livewire('services/create', 'pages::clinic.content.services.edit')->name('services.create');
            Route::livewire('services/{service}/edit', 'pages::clinic.content.services.edit')->name('services.edit');

            Route::livewire('blog', 'pages::clinic.content.blog.index')->name('blog.index');
            Route::livewire('blog/create', 'pages::clinic.content.blog.edit')->name('blog.create');
            Route::livewire('blog/categories', 'pages::clinic.content.blog.categories')->name('blog.categories');
            Route::livewire('blog/{post}/edit', 'pages::clinic.content.blog.edit')->name('blog.edit');

            Route::livewire('pages', 'pages::clinic.content.pages.index')->name('pages.index');
            Route::livewire('pages/{key}/edit', 'pages::clinic.content.pages.edit')->name('pages.edit');

            Route::livewire('gallery', 'pages::clinic.content.gallery.index')->name('gallery.index');
            Route::livewire('gallery/create', 'pages::clinic.content.gallery.edit')->name('gallery.create');
            Route::livewire('gallery/{item}/edit', 'pages::clinic.content.gallery.edit')->name('gallery.edit');

            Route::livewire('testimonials', 'pages::clinic.content.testimonials')->name('testimonials.index');
            Route::livewire('faqs', 'pages::clinic.content.faqs')->name('faqs.index');
            Route::livewire('media', 'pages::clinic.content.media')->name('media.index');
            Route::livewire('clinic-details', 'pages::clinic.content.clinic-details')->name('clinic-details');
            Route::livewire('messages', 'pages::clinic.content.messages')->name('messages.index');
        });

        // Administration
        // Staff accounts and dentist profiles. Components also re-check the permission on every action.
        Route::prefix('staff')->name('staff.')->middleware($can(Permission::StaffManage))->group(function () {
            Route::livewire('/', 'pages::clinic.staff.index')->name('index');
            Route::livewire('create', 'pages::clinic.staff.edit')->name('create');
            Route::livewire('{user}/edit', 'pages::clinic.staff.edit')->name('edit');
        });
        Route::view('settings', 'clinic.placeholder', $placeholder('Clinic settings', 3))
            ->middleware($can(Permission::SettingsManage))->name('settings.index');
        Route::view('audit', 'clinic.placeholder', $placeholder('Audit log', 6))
            ->middleware($can(Permission::AuditView))->name('audit.index');
    });
