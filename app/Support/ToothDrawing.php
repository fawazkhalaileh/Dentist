<?php

namespace App\Support;

use App\Enums\Dentition;
use App\Models\Procedure;

/**
 * Geometry for the dental chart. Each tooth is shown in a 40-unit-wide column:
 * a side view (40 x 78) and a top view (40 x 40), both rendered images of real
 * tooth models from BodyParts3D (CC BY-SA 2.1 JP, Life Science Integrated
 * Database Center), stored in public/images/teeth. The five surfaces are drawn
 * over the top view and clipped to the tooth's outline. Presentation only.
 */
final class ToothDrawing
{
    public const WIDTH = 40;

    public const SIDE_HEIGHT = 78;

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
     * Which rendered tooth image to use. Permanent teeth have their own renders
     * (from the BodyParts3D models); children's teeth reuse the matching adult
     * tooth, drawn smaller: primary molars use the first and second molars.
     */
    public static function imageTooth(int $tooth): int
    {
        if (Fdi::dentition($tooth) === Dentition::Permanent) {
            return $tooth;
        }

        $quadrant = Fdi::quadrant($tooth) - 4;
        $position = Fdi::position($tooth);

        return $quadrant * 10 + ($position >= 4 ? $position + 2 : $position);
    }

    public static function image(int $tooth, string $view): string
    {
        return asset('images/teeth/'.self::imageTooth($tooth).'-'.$view.'.webp');
    }

    /**
     * Inner (occlusal or incisal) area of the rendered top view, in the 40 x 40 box.
     *
     * @return array{0: float, 1: float, 2: float, 3: float}
     */
    public static function innerArea(string $type): array
    {
        return match ($type) {
            'incisor' => [12, 18.5, 28, 21.5],
            'canine' => [15.5, 17, 24.5, 23],
            'premolar' => [14, 15.5, 26, 24.5],
            default => [12.5, 13.5, 27.5, 26.5],
        };
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
