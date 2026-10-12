<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Renders staff-written Markdown for public pages. Raw HTML is stripped and
 * unsafe links (javascript:, data:) are dropped, so content cannot inject scripts.
 */
final class Markdown
{
    public static function toHtml(?string $markdown): string
    {
        if ($markdown === null || trim($markdown) === '') {
            return '';
        }

        return (string) Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 10,
        ]);
    }
}
