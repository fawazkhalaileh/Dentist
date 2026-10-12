<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * URL slugs that keep non-Latin letters, so Arabic pages get Arabic URLs
 * (e.g. /ar/services/تنظيف-الأسنان) instead of transliterated ones.
 */
final class Slug
{
    public const PATTERN = '/^[\p{L}\p{M}\p{N}]+(?:-[\p{L}\p{M}\p{N}]+)*$/u';

    public static function make(string $text): string
    {
        $slug = Str::lower(trim($text));
        // Anything that is not a letter, combining mark or digit becomes a separator.
        $slug = (string) preg_replace('/[^\p{L}\p{M}\p{N}]+/u', '-', $slug);

        return trim($slug, '-');
    }

    public static function isValid(string $slug): bool
    {
        return preg_match(self::PATTERN, $slug) === 1;
    }
}
