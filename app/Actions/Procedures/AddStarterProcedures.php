<?php

namespace App\Actions\Procedures;

use App\Models\Procedure;

/**
 * A short starter catalogue. Codes are internal identifiers, not an official
 * coding system; the clinic reviews names, durations and codes. Only codes that
 * are missing are added, so the clinic's own edits are never overwritten.
 */
class AddStarterProcedures
{
    public const PROCEDURES = [
        ['code' => 'EXAM', 'name' => 'Examination', 'name_ar' => 'فحص', 'category' => 'diagnostic', 'default_duration_minutes' => 30, 'scope' => 'mouth'],
        ['code' => 'XRAY', 'name' => 'X-ray', 'name_ar' => 'صورة أشعة', 'category' => 'diagnostic', 'default_duration_minutes' => 15, 'scope' => 'tooth'],
        ['code' => 'SCALE', 'name' => 'Scaling and polishing', 'name_ar' => 'تنظيف الجير وتلميع', 'category' => 'preventive', 'default_duration_minutes' => 45, 'scope' => 'mouth'],
        ['code' => 'FILL', 'name' => 'Filling', 'name_ar' => 'حشوة', 'category' => 'restorative', 'default_duration_minutes' => 45, 'scope' => 'surface'],
        ['code' => 'RCT', 'name' => 'Root canal treatment', 'name_ar' => 'علاج عصب', 'category' => 'endodontic', 'default_duration_minutes' => 90, 'scope' => 'tooth'],
        ['code' => 'EXT', 'name' => 'Extraction', 'name_ar' => 'خلع', 'category' => 'surgical', 'default_duration_minutes' => 45, 'scope' => 'tooth'],
        ['code' => 'CROWN', 'name' => 'Crown', 'name_ar' => 'تاج', 'category' => 'prosthodontic', 'default_duration_minutes' => 60, 'scope' => 'tooth'],
    ];

    /**
     * @return int number of procedures added
     */
    public function handle(): int
    {
        $added = 0;

        foreach (self::PROCEDURES as $procedure) {
            $added += Procedure::firstOrCreate(['code' => $procedure['code']], $procedure)->wasRecentlyCreated ? 1 : 0;
        }

        return $added;
    }
}
