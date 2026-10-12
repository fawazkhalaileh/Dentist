<?php

namespace App\Providers;

use App\Http\Middleware\EnsureStaffHasTwoFactor;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SetLocale;
use App\Models\Patient;
use App\Support\RolesAndPermissions;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Events\NoPendingMigrations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        // Roles and permissions are reference data the app cannot run without, so every
        // `migrate` (including one with nothing to migrate) brings them up to date.
        Event::listen(function (MigrationsEnded|NoPendingMigrations $event) {
            $pretend = $event instanceof MigrationsEnded && ($event->options['pretend'] ?? false);

            if ($event->method === 'up' && ! $pretend) {
                RolesAndPermissions::sync();
            }
        });

        // Explicit binding so {patient} resolves on Route::view routes too.
        // Archived (soft-deleted) patients resolve to 404.
        Route::model('patient', Patient::class);

        // Livewire update requests re-run these from the original route, so
        // staff MFA, deactivation and locale also apply to component actions.
        Livewire::addPersistentMiddleware([
            SetLocale::class,
            EnsureUserIsActive::class,
            EnsureStaffHasTwoFactor::class,
        ]);

        RateLimiter::for('contact', fn (Request $request) => [
            Limit::perMinute(3)->by($request->ip()),
            Limit::perDay(20)->by($request->ip()),
        ]);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        // Strong passwords everywhere; the breached-password lookup needs network access, so production only.
        Password::defaults(function (): Password {
            $rule = Password::min(12)->mixedCase()->letters()->numbers()->symbols();

            return app()->isProduction() ? $rule->uncompromised() : $rule;
        });
    }
}
