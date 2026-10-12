<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public pages take the locale from the URL (/en/..., /ar/...).
 * Portal and clinic pages use the signed-in user's preferred locale.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeLocale = $request->route()?->parameter('locale');
        $user = $request->user();

        $locale = match (true) {
            is_string($routeLocale) && Locales::isSupported($routeLocale) => $routeLocale,
            $user instanceof User && Locales::isSupported($user->locale) => $user->locale,
            default => Locales::default(),
        };

        App::setLocale($locale);

        // Lets route('public.services.index') work without passing the locale every time.
        URL::defaults(['locale' => $locale]);

        // The locale is a routing detail; controllers and views do not need it as an argument.
        $request->route()?->forgetParameter('locale');

        return $next($request);
    }
}
