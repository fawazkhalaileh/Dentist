<?php

namespace Database\Seeders;

use App\Models\Procedure;
use Illuminate\Database\Seeder;

/**
 * Starter procedure catalogue. Codes are internal identifiers, not an official
 * coding system; the clinic should review names, durations and codes.
 */
class ProcedureSeeder extends Seeder
{
    public function run(): void
    {
        $procedures = [
            ['code' => 'EXAM', 'name' => 'Examination', 'name_ar' => 'فحص', 'category' => 'diagnostic', 'default_duration_minutes' => 30, 'scope' => 'mouth'],
            ['code' => 'SCALE', 'name' => 'Scaling and polishing', 'name_ar' => 'تنظيف الجير وتلميع', 'category' => 'preventive', 'default_duration_minutes' => 45, 'scope' => 'mouth'],
            ['code' => 'FILL', 'name' => 'Filling', 'name_ar' => 'حشوة', 'category' => 'restorative', 'default_duration_minutes' => 45, 'scope' => 'surface'],
            ['code' => 'RCT', 'name' => 'Root canal treatment', 'name_ar' => 'علاج عصب', 'category' => 'endodontic', 'default_duration_minutes' => 90, 'scope' => 'tooth'],
            ['code' => 'EXT', 'name' => 'Extraction', 'name_ar' => 'خلع', 'category' => 'surgical', 'default_duration_minutes' => 45, 'scope' => 'tooth'],
            ['code' => 'CROWN', 'name' => 'Crown', 'name_ar' => 'تاج', 'category' => 'prosthodontic', 'default_duration_minutes' => 60, 'scope' => 'tooth'],
        ];

        foreach ($procedures as $procedure) {
            Procedure::updateOrCreate(['code' => $procedure['code']], $procedure);
        }
    }
}
