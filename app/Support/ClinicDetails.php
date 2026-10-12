<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

/**
 * Editable clinic name and contact details shown on the public site (cached).
 */
final class ClinicDetails
{
    private const KEY = 'clinic.contact';

    private const CACHE_KEY = 'settings.clinic.contact';

    /**
     * Fields and whether they are per-locale.
     *
     * @var array<string, bool>
     */
    public const FIELDS = [
        'name' => true,
        'phone' => false,
        'email' => false,
        'whatsapp' => false,
        'map_url' => false,
        'address' => true,
        'opening_hours' => true,
    ];

    /**
     * @return array{name: array<string, ?string>, phone: ?string, email: ?string, whatsapp: ?string, map_url: ?string, address: array<string, ?string>, opening_hours: array<string, ?string>}
     */
    public static function all(): array
    {
        $stored = Cache::rememberForever(self::CACHE_KEY, fn () => Setting::find(self::KEY)->value ?? []);

        $text = fn (mixed $value): ?string => is_string($value) && $value !== '' ? $value : null;

        $localized = function (string $field) use ($stored, $text): array {
            $values = is_array($stored[$field] ?? null) ? $stored[$field] : [];
            $out = [];
            foreach (Locales::supported() as $locale) {
                $out[$locale] = $text($values[$locale] ?? null);
            }

            return $out;
        };

        return [
            'name' => $localized('name'),
            'phone' => $text($stored['phone'] ?? null),
            'email' => $text($stored['email'] ?? null),
            'whatsapp' => $text($stored['whatsapp'] ?? null),
            'map_url' => $text($stored['map_url'] ?? null),
            'address' => $localized('address'),
            'opening_hours' => $localized('opening_hours'),
        ];
    }

    /**
     * The clinic's name in the locale (current by default), falling back to the
     * default language and then to config('clinic.name').
     */
    public static function name(?string $locale = null): string
    {
        return self::get('name', $locale)
            ?? self::get('name', Locales::default())
            ?? (string) config('clinic.name');
    }

    public static function get(string $field, ?string $locale = null): ?string
    {
        $value = self::all()[$field] ?? null;

        if (is_array($value)) {
            $value = $value[$locale ?? App::getLocale()] ?? null;
        }

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $details
     */
    public static function put(array $details, ?int $userId = null): void
    {
        Setting::updateOrCreate(
            ['key' => self::KEY],
            ['value' => array_intersect_key($details, self::FIELDS), 'updated_by' => $userId],
        );

        Cache::forget(self::CACHE_KEY);
    }
}
