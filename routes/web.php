<?php

use App\Http\Controllers\Public\BlogController;
use App\Http\Controllers\Public\BookingController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\DentistController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\PublicContentController;
use App\Http\Controllers\Public\ServiceController;
use App\Http\Controllers\Public\SitemapController;
use App\Models\User;
use App\Support\Locales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website
|--------------------------------------------------------------------------
| Every public page lives under a locale prefix (/en/..., /ar/...).
| Content is read only from published records; nothing clinical is exposed here.
*/

// Redirect the bare root to the visitor's preferred supported locale.
Route::get('/', function (Request $request) {
    $locale = $request->getPreferredLanguage(Locales::supported()) ?? Locales::default();

    return redirect()->route('public.home', ['locale' => $locale]);
})->name('home');

Route::get('sitemap.xml', SitemapController::class)->name('sitemap');

Route::prefix('{locale}')
    ->where(['locale' => Locales::routePattern()])
    ->name('public.')
    ->group(function () {
        Route::get('/', HomeController::class)->name('home');

        Route::get('about', PageController::class)->defaults('page', 'about')->name('about');
        Route::view('meet-dr-mohammed-amara', 'public.doctor')->name('doctor');
        // Earlier address of the doctor page, kept so shared links still work.
        Route::get('meet-dr-moi', fn () => redirect()->route('public.doctor', ['locale' => app()->getLocale()], 301));
        Route::get('privacy', PageController::class)->defaults('page', 'privacy')->name('legal.privacy');
        Route::get('terms', PageController::class)->defaults('page', 'terms')->name('legal.terms');

        Route::get('dentists', [DentistController::class, 'index'])->name('dentists.index');
        Route::get('dentists/{slug}', [DentistController::class, 'show'])->name('dentists.show');
        Route::get('services', [ServiceController::class, 'index'])->name('services.index');
        Route::get('services/{slug}', [ServiceController::class, 'show'])->name('services.show');
        Route::get('blog', [BlogController::class, 'index'])->name('blog.index');
        Route::get('blog/category/{slug}', [BlogController::class, 'category'])->name('blog.category');
        Route::get('blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

        Route::get('gallery', [PublicContentController::class, 'gallery'])->name('gallery');
        Route::get('testimonials', [PublicContentController::class, 'testimonials'])->name('testimonials');
        Route::get('faq', [PublicContentController::class, 'faq'])->name('faq');

        Route::get('contact', [ContactController::class, 'show'])->name('contact');
        Route::post('contact', [ContactController::class, 'store'])->middleware('throttle:contact')->name('contact.store');

        Route::get('book', [BookingController::class, 'show'])->name('booking');
        Route::post('book', [BookingController::class, 'store'])->middleware('throttle:contact')->name('booking.store');
    });

/*
|--------------------------------------------------------------------------
| Post-login landing
|--------------------------------------------------------------------------
| Fortify sends users to /dashboard; this forwards them to their area.
*/

Route::middleware(['auth', 'verified', 'active'])->get('dashboard', function (Request $request) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user->isStaff() || $user->hasRole('patient'), 403);

    return redirect()->to($user->homeRoute());
})->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/portal.php';
require __DIR__.'/clinic.php';
