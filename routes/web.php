<?php

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
| Pages marked with a phase number are routed placeholders, not finished pages.
*/

Route::get('/', function (Request $request) {
    $locale = $request->getPreferredLanguage(Locales::supported()) ?? Locales::default();

    return redirect()->route('public.home', ['locale' => $locale]);
})->name('home');

Route::get('sitemap.xml', SitemapController::class)->name('sitemap');

Route::prefix('{locale}')
    ->where(['locale' => Locales::routePattern()])
    ->name('public.')
    ->group(function () {
        Route::view('/', 'public.home')->name('home');

        $placeholder = fn (string $title, int $phase) => ['title' => $title, 'phase' => $phase];

        Route::view('about', 'public.placeholder', $placeholder('About the clinic', 2))->name('about');
        Route::view('dentists', 'public.placeholder', $placeholder('Meet the dentists', 2))->name('dentists.index');
        Route::view('dentists/{slug}', 'public.placeholder', $placeholder('Dentist profile', 2))->name('dentists.show');
        Route::view('services', 'public.placeholder', $placeholder('Services', 2))->name('services.index');
        Route::view('services/{slug}', 'public.placeholder', $placeholder('Service', 2))->name('services.show');
        Route::view('blog', 'public.placeholder', $placeholder('Blog', 2))->name('blog.index');
        Route::view('blog/category/{slug}', 'public.placeholder', $placeholder('Blog category', 2))->name('blog.category');
        Route::view('blog/{slug}', 'public.placeholder', $placeholder('Article', 2))->name('blog.show');
        Route::view('gallery', 'public.placeholder', $placeholder('Before and after gallery', 2))->name('gallery');
        Route::view('testimonials', 'public.placeholder', $placeholder('Testimonials', 2))->name('testimonials');
        Route::view('faq', 'public.placeholder', $placeholder('Frequently asked questions', 2))->name('faq');
        Route::view('contact', 'public.placeholder', $placeholder('Contact', 2))->name('contact');
        Route::view('book', 'public.placeholder', $placeholder('Request an appointment', 5))->name('booking');
        Route::view('privacy', 'public.placeholder', $placeholder('Privacy policy', 2))->name('legal.privacy');
        Route::view('terms', 'public.placeholder', $placeholder('Terms of use', 2))->name('legal.terms');
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
