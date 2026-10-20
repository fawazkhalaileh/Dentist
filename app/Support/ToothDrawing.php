<?php

namespace App\Support;

use App\Enums\Dentition;
use App\Models\Procedure;

/**
 * Shapes for the dental chart drawing. Every tooth is drawn in a 40-unit-wide
 * local box: a side view (crown and roots, 78 high, roots up as for an upper
 * tooth; the chart flips it for the lower arch) and a top (occlusal) view
 * (40 x 40) split into the five surfaces. Presentation only.
 */
final class ToothDrawing
{
    public const WIDTH = 40;

    public const SIDE_HEIGHT = 78;

    public const TOP_HEIGHT = 40;

    /**
     * @return 'incisor'|'canine'|'premolar'|'molar'
     */
    public static function type(int $tooth): string
    {
        $position = Fdi::position($tooth);
        $primary = Fdi::dentition($tooth) === Dentition::Primary;

        return match (true) {
            $position <= 2 => 'incisor',
            $position === 3 => 'canine',
            $primary || $position >= 6 => 'molar',
            default => 'premolar',
        };
    }

    /**
     * Side view: crown outline, root outlines and the canal line(s) inside the roots.
     *
     * @return array{crown: string, roots: list<string>, canals: list<string>, cej: string}
     */
    public static function side(string $type): array
    {
        return match ($type) {
            'incisor' => [
                'crown' => 'M10,46 C10,42 30,42 30,46 L31.5,66 C32,73 27,77 20,77 C13,77 8,73 8.5,66 Z',
                'roots' => ['M11,47 C11,32 15,10 20,5 C25,10 29,32 29,47 Z'],
                'canals' => ['M20,60 L20,10'],
                'cej' => 'M10,46.5 C14,43 26,43 30,46.5',
            ],
            'canine' => [
                'crown' => 'M10,46 C10,42 30,42 30,46 L31.5,63 C32,69 25,74 20,78 C15,74 8,69 8.5,63 Z',
                'roots' => ['M11,47 C11,30 16,6 20,1 C24,6 29,30 29,47 Z'],
                'canals' => ['M20,62 L20,6'],
                'cej' => 'M10,46.5 C14,43 26,43 30,46.5',
            ],
            'premolar' => [
                'crown' => 'M8,46 C8,41.5 32,41.5 32,46 L33,63 C33.5,70 30.5,75 26.5,75 C24,75 22.5,72.5 20,72.5 C17.5,72.5 16,75 13.5,75 C9.5,75 6.5,70 7,63 Z',
                'roots' => ['M11,47 C11,31 15,12 20,7 C25,12 29,31 29,47 Z'],
                'canals' => ['M20,62 L20,12'],
                'cej' => 'M8,46.5 C13,43 27,43 32,46.5',
            ],
            default => [
                'crown' => 'M4,46 C4,40.5 36,40.5 36,46 L37,62 C37.5,70 34.5,75 30.5,75 C27.5,75 26.5,71.5 23.5,71.5 L16.5,71.5 C13.5,71.5 12.5,75 9.5,75 C5.5,75 2.5,70 3,62 Z',
                'roots' => [
                    'M6.5,47 C5.5,34 7,18 10.5,10 C13.5,14 16.5,30 18,47 Z',
                    'M22,47 C23.5,30 26.5,14 29.5,10 C33,18 34.5,34 33.5,47 Z',
                ],
                'canals' => ['M14,60 L11,15', 'M26,60 L29,15'],
                'cej' => 'M4,46.5 C11,42.5 29,42.5 36,46.5',
            ],
        };
    }

    /**
     * Top view: outline, the inner (occlusal or incisal) area and a decorative groove.
     * The outer box is the outline's bounding box; the five surfaces are drawn as
     * trapezoids from that box to the inner area, clipped to the outline.
     *
     * @return array{outline: string, box: array{0: float, 1: float, 2: float, 3: float}, inner: array{0: float, 1: float, 2: float, 3: float}, groove: ?string}
     */
    public static function top(string $type): array
    {
        return match ($type) {
            'incisor' => [
                'outline' => 'M7,13 C7,10 33,10 33,13 C35,16 35,24 33,27 C33,30 7,30 7,27 C5,24 5,16 7,13 Z',
                'box' => [5, 10, 35, 30],
                'inner' => [11, 18.5, 29, 21.5],
                'groove' => null,
            ],
            'canine' => [
                'outline' => 'M20,6 C27,8 34,14 34,20 C34,26 27,33 20,35 C13,33 6,26 6,20 C6,14 13,8 20,6 Z',
                'box' => [6, 6, 34, 35],
                'inner' => [15, 17.5, 25, 23.5],
                'groove' => null,
            ],
            'premolar' => [
                'outline' => 'M9,9 C14,5 26,5 31,9 C35,13 35,27 31,31 C26,35 14,35 9,31 C5,27 5,13 9,9 Z',
                'box' => [6, 6, 34, 34],
                'inner' => [13.5, 15, 26.5, 25],
                'groove' => 'M14,20 C17,18.5 23,18.5 26,20',
            ],
            default => [
                'outline' => 'M8,5 C15,3 25,3 32,5 C37,7 38,13 37.5,20 C38,27 37,33 32,35 C25,37 15,37 8,35 C3,33 2,27 2.5,20 C2,13 3,7 8,5 Z',
                'box' => [2.5, 3.5, 37.5, 36.5],
                'inner' => [12, 13, 28, 27],
                'groove' => 'M12,20 L28,20 M20,13 L20,27 M14,15 L18,18.5 M26,15 L22,18.5 M14,25 L18,21.5 M26,25 L22,21.5',
            ],
        };
    }

    /**
     * Trapezoids for the four outer surfaces of the top view, keyed by side.
     *
     * @param  array{0: float, 1: float, 2: float, 3: float}  $box
     * @param  array{0: float, 1: float, 2: float, 3: float}  $inner
     * @return array{top: string, bottom: string, left: string, right: string}
     */
    public static function regions(array $box, array $inner): array
    {
        [$x1, $y1, $x2, $y2] = $box;
        [$i1, $j1, $i2, $j2] = $inner;

        return [
            'top' => "{$x1},{$y1} {$x2},{$y1} {$i2},{$j1} {$i1},{$j1}",
            'bottom' => "{$x1},{$y2} {$x2},{$y2} {$i2},{$j2} {$i1},{$j2}",
            'left' => "{$x1},{$y1} {$i1},{$j1} {$i1},{$j2} {$x1},{$y2}",
            'right' => "{$x2},{$y1} {$i2},{$j1} {$i2},{$j2} {$x2},{$y2}",
        ];
    }

    /**
     * How a whole-tooth procedure is drawn: a crown cap, a canal line, an
     * extraction cross, an implant post, or (anything else) a tint of the tooth.
     *
     * @return 'crown'|'canal'|'extraction'|'implant'|'tint'
     */
    public static function marker(Procedure $procedure): string
    {
        $code = strtoupper((string) $procedure->code);

        return match (true) {
            str_starts_with($code, 'EXT') || $code === 'WISDOM' => 'extraction',
            $code === 'IMPL' => 'implant',
            $procedure->category === 'endodontic' => 'canal',
            in_array($code, ['CROWN', 'IMPL-CROWN', 'BRIDGE', 'VENEER', 'CORE'], true) => 'crown',
            default => 'tint',
        };
    }
}
