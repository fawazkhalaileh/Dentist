<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Amounts are integers in minor units (fils: 1 JD = 1000 fils). This class
 * converts between typed amounts ("25", "25.5") and minor units, and formats
 * them for display ("25 JD", "25.500 JD", "25 د.أ").
 */
final class Money
{
    public static function decimals(): int
    {
        return (int) config('clinic.currency.decimals', 3);
    }

    /**
     * "25.5" → 25500. Rejects negative values, letters and too many decimals.
     */
    public static function parse(string $input): int
    {
        $input = self::normalise($input);
        $decimals = self::decimals();

        if (! preg_match('/^\d{1,9}(\.\d{1,'.$decimals.'})?$/', $input)) {
            throw new InvalidArgumentException("Not an amount: {$input}");
        }

        [$whole, $fraction] = array_pad(explode('.', $input, 2), 2, '');

        return (int) $whole * (10 ** $decimals) + (int) str_pad($fraction, $decimals, '0');
    }

    /**
     * Accept a comma as the decimal point and Arabic-Indic digits (٠١٢…).
     */
    public static function normalise(string $input): string
    {
        $input = strtr(trim($input), ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9', '٫' => '.', ',' => '.', ' ' => '']);

        return $input;
    }

    /**
     * Validation rule for a typed amount (after normalise()).
     */
    public static function rule(): string
    {
        return 'regex:/^\d{1,9}(\.\d{1,'.self::decimals().'})?$/';
    }

    /**
     * 25500 → "25.500", 25000 → "25" (for form fields).
     */
    public static function plain(?int $minor): string
    {
        if ($minor === null) {
            return '';
        }

        $decimals = self::decimals();
        $sign = $minor < 0 ? '-' : '';
        $minor = abs($minor);
        $whole = intdiv($minor, 10 ** $decimals);
        $fraction = $minor % (10 ** $decimals);

        return $sign.$whole.($fraction === 0 ? '' : '.'.str_pad((string) $fraction, $decimals, '0', STR_PAD_LEFT));
    }

    /**
     * 25500 → "25.500 JD" (or "25.500 د.أ" in Arabic), with thousands separators.
     */
    public static function format(?int $minor, ?string $locale = null): string
    {
        if ($minor === null) {
            return '–';
        }

        $decimals = self::decimals();
        $fraction = abs($minor) % (10 ** $decimals);
        $number = number_format($minor / (10 ** $decimals), $fraction === 0 ? 0 : $decimals);
        $symbol = config('clinic.currency.symbol.'.($locale ?? app()->getLocale()), config('clinic.currency.code'));

        return $number.' '.$symbol;
    }
}
