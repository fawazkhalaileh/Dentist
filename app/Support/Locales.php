<?php

namespace App\Support;

use Illuminate\Support\Facades\App;

final class Locales
{
    /**
     * @return list<string>
     */
    public static function supported(): array
    {
        return array_values(array_filter(array_keys(config('clinic.locales')), 'is_string'));
    }

    public static function isSupported(?string $locale): bool
    {
        return $locale !== null && in_array($locale, self::supported(), true);
    }

    public static function default(): string
    {
        $default = config('clinic.default_locale');

        return self::isSupported($default) ? $default : self::supported()[0];
    }

    public static function dir(?string $locale = null): string
    {
        $locale ??= App::getLocale();

        return config("clinic.locales.{$locale}.dir", 'ltr');
    }

    public static function nativeName(string $locale): string
    {
        return config("clinic.locales.{$locale}.native", $locale);
    }

    /**
     * Regex for the {locale} route parameter, e.g. "en|ar".
     */
    public static function routePattern(): string
    {
        return implode('|', array_map(fn (string $l) => preg_quote($l, '/'), self::supported()));
    }
}
