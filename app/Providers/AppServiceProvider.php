<?php

namespace App\Providers;

use App\Http\Middleware\SetLocale;
use App\Models\Patient;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
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

        // Explicit binding so {patient} resolves on Route::view routes too.
        // Archived (soft-deleted) patients resolve to 404.
        Route::model('patient', Patient::class);

        // Re-apply the request locale on Livewire update requests.
        Livewire::addPersistentMiddleware([SetLocale::class]);
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
