<?php

use App\Enums\Permission;
use App\Http\Controllers\Portal\PortalDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Patient portal
|--------------------------------------------------------------------------
| Patients only ever see records linked to their own account; every query in
| this area starts from $request->user()->patient.
*/

Route::middleware(['auth', 'verified', 'active', 'can:'.Permission::PortalAccess->value])
    ->prefix('portal')
    ->name('portal.')
    ->group(function () {
        Route::get('/', PortalDashboardController::class)->name('dashboard');

        $placeholder = fn (string $title, int $phase) => ['title' => $title, 'phase' => $phase];

        Route::view('appointments', 'portal.placeholder', $placeholder('My appointments', 5))->name('appointments');
        Route::view('treatments', 'portal.placeholder', $placeholder('My treatments', 5))->name('treatments');
        Route::view('documents', 'portal.placeholder', $placeholder('My documents', 5))->name('documents');
    });
