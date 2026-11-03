<?php

use App\Http\Controllers\Public\BlogController;
use App\Http\Controllers\Public\BookingController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\CrawlerController;
use App\Http\Controllers\Public\DentistController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\PublicContentController;
use App\Http\Controllers\Public\ServiceController;
use App\Http\Controllers\Public\SitemapController;
use App\Models\User;
use App\Support\Locales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
Route::get('robots.txt', [CrawlerController::class, 'robots'])->name('robots');
Route::get('llms.txt', [CrawlerController::class, 'llms'])->name('llms');

Route::prefix('{locale}')
    ->where(['locale' => Locales::routePattern()])
    ->name('public.')
    ->group(function () {
        Route::get('/', HomeController::class)->name('home');

        Route::get('about', PageController::class)->defaults('page', 'about')->name('about');
        Route::view('meet-dr-mohammad-amara', 'public.doctor')->name('doctor');
        // Earlier addresses of the doctor page, kept so shared links still work.
        Route::get('meet-dr-moi', fn () => redirect()->route('public.doctor', ['locale' => app()->getLocale()], 301));
        Route::get('meet-dr-mohammed-amara', fn () => redirect()->route('public.doctor', ['locale' => app()->getLocale()], 301));
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

// A password-setup link opened while signed in: explain, then sign out and continue to it.
Route::middleware('auth')->group(function () {
    $resetLink = function (Request $request): ?string {
        $next = (string) $request->input('next', '');
        $path = parse_url($next, PHP_URL_PATH);
        $host = parse_url($next, PHP_URL_HOST);

        // Only ever continue to a password-reset link on this site (no open redirect).
        return is_string($path) && str_starts_with($path, '/reset-password/') && ($host === null || $host === $request->getHost()) ? $next : null;
    };

    Route::get('setup-link/signed-in', function (Request $request) use ($resetLink) {
        $next = $resetLink($request) ?? abort(404);
        parse_str((string) parse_url($next, PHP_URL_QUERY), $query);

        return view('auth.setup-link-signed-in', ['next' => $next, 'email' => $query['email'] ?? null]);
    })->name('setup-link.signed-in');

    Route::post('setup-link/continue', function (Request $request) use ($resetLink) {
        $next = $resetLink($request) ?? abort(404);
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to($next);
    })->name('setup-link.continue');
});

Route::middleware(['auth', 'verified', 'active'])->get('dashboard', function (Request $request) {
    /** @var User $user */
    $user = $request->user();

    abort_unless($user->isStaff() || $user->hasRole('patient'), 403);

    return redirect()->to($user->homeRoute());
})->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/portal.php';
require __DIR__.'/clinic.php';
