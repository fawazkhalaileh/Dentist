<?php

namespace App\Support\Site;

use App\Models\Media;

/**
 * Display-only alignment of a before/after pair inside the 4:3 comparison frame: both
 * photos are scaled so the mouth has the same width and shifted so the smile sits in the
 * same place, while still covering the whole frame. Photos are never modified.
 */
final class SmileAlignment
{
    /** Where the smile should sit in the frame (percent of width / height). */
    private const TARGET_X = 50.0;

    private const TARGET_Y = 48.0;

    private const FRAME_RATIO = 4 / 3;

    /**
     * Inline styles for the two images, or null when the pair has not been measured.
     *
     * @return array{before: string, after: string}|null
     */
    public static function styles(?Media $before, ?Media $after): ?array
    {
        $points = config('site.smile_alignment', []);
        $b = $before ? ($points[$before->id] ?? null) : null;
        $a = $after ? ($points[$after->id] ?? null) : null;

        if (! $before || ! $after || ! $b || ! $a || ! $before->width || ! $after->width) {
            return null;
        }

        // One mouth width (percent of the frame width) for both photos: the smallest that lets
        // each photo cover the frame with its smile exactly on the target point.
        $mouth = 0.0;
        foreach ([[$before, $b], [$after, $a]] as [$media, $p]) {
            $toHeight = ($media->height / $media->width) * self::FRAME_RATIO;   // image height (% of frame) per 1% of width
            $minWidth = max(
                100.0,
                self::TARGET_X / max($p['cx'], 0.01), (100 - self::TARGET_X) / max(1 - $p['cx'], 0.01),
                self::TARGET_Y / max($p['cy'], 0.01) / $toHeight, (100 - self::TARGET_Y) / max(1 - $p['cy'], 0.01) / $toHeight,
            );
            $mouth = max($mouth, $minWidth * $p['w']);
        }

        return [
            'before' => self::style($before, $b, $mouth),
            'after' => self::style($after, $a, $mouth),
        ];
    }

    /**
     * @param  array{cx: float, cy: float, w: float}  $p
     */
    private static function style(Media $media, array $p, float $mouth): string
    {
        $width = $mouth / $p['w'];                                              // % of frame width
        $height = $width * ($media->height / $media->width) * self::FRAME_RATIO; // % of frame height
        $left = min(0.0, max(100.0 - $width, self::TARGET_X - $p['cx'] * $width));
        $top = min(0.0, max(100.0 - $height, self::TARGET_Y - $p['cy'] * $height));

        return sprintf('width:%.2f%%;height:%.2f%%;left:%.2f%%;top:%.2f%%;max-width:none;object-fit:fill', $width, $height, $left, $top);
    }
}
