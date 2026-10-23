<?php

/*
|--------------------------------------------------------------------------
| Clinic configuration
|--------------------------------------------------------------------------
|
| Clinic-wide settings that are not secrets. Branding (colours, fonts) lives
| in resources/css/theme.css so the final visual identity can be applied
| without touching backend code.
|
*/

return [

    'name' => env('APP_NAME', 'Dental Clinic'),

    // Business timezone used for schedules and appointment display.
    // Timestamps are stored in UTC (config/app.php).
    'timezone' => env('CLINIC_TIMEZONE', 'UTC'),

    /*
    | Supported locales. Public website URLs are prefixed with the locale
    | (/en/..., /ar/...). Portal and clinic areas use the user's locale.
    */
    'locales' => [
        'en' => ['name' => 'English', 'native' => 'English', 'dir' => 'ltr'],
        'ar' => ['name' => 'Arabic', 'native' => 'العربية', 'dir' => 'rtl'],
    ],

    'default_locale' => env('APP_LOCALE', 'en'),

    /*
    | Money. Amounts are stored as whole minor units (fils for the Jordanian
    | dinar, which has 3 decimals) so sums never suffer from rounding.
    */
    'currency' => [
        'code' => env('CLINIC_CURRENCY', 'JOD'),
        'decimals' => (int) env('CLINIC_CURRENCY_DECIMALS', 3),
        'symbol' => ['en' => 'JD', 'ar' => 'د.أ'],
    ],

    'patients' => [
        // Patient numbers look like P-000123.
        'number_prefix' => env('CLINIC_PATIENT_NUMBER_PREFIX', 'P-'),
        'number_padding' => 6,
    ],

    'security' => [
        // Staff (admin, dentist, receptionist) must confirm two-factor
        // authentication before using the clinic dashboard.
        // Only applies while two-factor sign-in is enabled (CLINIC_TWO_FACTOR).
        'require_staff_mfa' => (bool) env('CLINIC_TWO_FACTOR', true) && (bool) env('CLINIC_REQUIRE_STAFF_MFA', true),
    ],

    'storage' => [
        // Private disk for clinical uploads. Never a public disk.
        'clinical_disk' => env('CLINIC_CLINICAL_DISK', 'local'),
    ],

];
