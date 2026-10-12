<?php

namespace App\Support;

use App\Enums\Dentition;
use App\Enums\ToothSurface;

/**
 * FDI two-digit tooth notation.
 *
 * First digit = quadrant (1-4 permanent, 5-8 primary), clockwise from the
 * patient's upper right. Second digit = position from the midline
 * (1-8 permanent, 1-5 primary).
 */
final class Fdi
{
    /**
     * @return list<int>
     */
    public static function permanent(): array
    {
        return self::numbers([1, 2, 3, 4], 8);
    }

    /**
     * @return list<int>
     */
    public static function primary(): array
    {
        return self::numbers([5, 6, 7, 8], 5);
    }

    public static function isValid(int $number): bool
    {
        return in_array($number, self::permanent(), true)
            || in_array($number, self::primary(), true);
    }

    public static function quadrant(int $number): int
    {
        return intdiv($number, 10);
    }

    public static function position(int $number): int
    {
        return $number % 10;
    }

    /**
     * Incisors and canines (positions 1-3 in both dentitions).
     */
    public static function isAnterior(int $number): bool
    {
        return self::position($number) <= 3;
    }

    /**
     * Surfaces a tooth has: incisal edge for front teeth, occlusal for back teeth.
     *
     * @return list<ToothSurface>
     */
    public static function surfaces(int $number): array
    {
        return self::isAnterior($number)
            ? [ToothSurface::Mesial, ToothSurface::Distal, ToothSurface::Buccal, ToothSurface::Lingual, ToothSurface::Incisal]
            : [ToothSurface::Mesial, ToothSurface::Occlusal, ToothSurface::Distal, ToothSurface::Buccal, ToothSurface::Lingual];
    }

    public static function dentition(int $number): Dentition
    {
        return self::quadrant($number) <= 4 ? Dentition::Permanent : Dentition::Primary;
    }

    public static function arch(int $number): string
    {
        return in_array(self::quadrant($number), [1, 2, 5, 6], true) ? 'upper' : 'lower';
    }

    /**
     * English descriptive name, e.g. "Upper right central incisor".
     */
    public static function name(int $number): string
    {
        $side = in_array(self::quadrant($number), [1, 4, 5, 8], true) ? 'right' : 'left';
        $arch = self::arch($number);

        $types = self::dentition($number) === Dentition::Permanent
            ? [1 => 'central incisor', 'lateral incisor', 'canine', 'first premolar', 'second premolar', 'first molar', 'second molar', 'third molar']
            : [1 => 'central incisor', 'lateral incisor', 'canine', 'first molar', 'second molar'];

        return ucfirst("{$arch} {$side} {$types[self::position($number)]}");
    }

    /**
     * Rows for the teeth reference table.
     *
     * @return list<array{fdi_number: int, dentition: string, quadrant: int, position: int, arch: string, name: string}>
     */
    public static function referenceRows(): array
    {
        return array_map(fn (int $n) => [
            'fdi_number' => $n,
            'dentition' => self::dentition($n)->value,
            'quadrant' => self::quadrant($n),
            'position' => self::position($n),
            'arch' => self::arch($n),
            'name' => self::name($n),
        ], [...self::permanent(), ...self::primary()]);
    }

    /**
     * @param  list<int>  $quadrants
     * @return list<int>
     */
    private static function numbers(array $quadrants, int $teethPerQuadrant): array
    {
        $numbers = [];

        foreach ($quadrants as $quadrant) {
            foreach (range(1, $teethPerQuadrant) as $position) {
                $numbers[] = $quadrant * 10 + $position;
            }
        }

        return $numbers;
    }
}
