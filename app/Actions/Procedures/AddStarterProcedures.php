<?php

namespace App\Actions\Procedures;

use App\Models\Procedure;

/**
 * A general-dentistry starter catalogue in English and Arabic, with chart
 * colours. Codes are internal identifiers, not an official coding system; the
 * clinic reviews names, durations and colours. Missing codes are added; for
 * existing codes only an empty colour is filled in, so the clinic's own edits
 * are never overwritten. Used by the "Add starter procedures" button and by
 * ProcedureSeeder (`php artisan db:seed --class=ProcedureSeeder --force`).
 */
class AddStarterProcedures
{
    /**
     * [code, English, Arabic, category, minutes, scope, colour]
     *
     * @var list<array{0: string, 1: string, 2: string, 3: string, 4: int, 5: string, 6: string}>
     */
    private const LIST = [
        // Diagnostic
        ['EXAM', 'Examination', 'فحص', 'diagnostic', 30, 'mouth', '#64748b'],
        ['CONSULT', 'Consultation', 'استشارة', 'diagnostic', 20, 'mouth', '#64748b'],
        ['XRAY', 'X-ray', 'صورة أشعة', 'diagnostic', 15, 'tooth', '#94a3b8'],
        ['XRAY-BW', 'Bitewing X-rays', 'صور أشعة جناحية', 'diagnostic', 15, 'mouth', '#94a3b8'],
        ['OPG', 'Panoramic X-ray', 'صورة بانورامية', 'diagnostic', 15, 'mouth', '#94a3b8'],
        // Preventive
        ['SCALE', 'Scaling and polishing', 'تنظيف الجير وتلميع', 'preventive', 45, 'mouth', '#0ea5e9'],
        ['FLUOR', 'Fluoride application', 'تطبيق الفلورايد', 'preventive', 15, 'mouth', '#38bdf8'],
        ['SEAL', 'Fissure sealant', 'سد الشقوق', 'preventive', 20, 'tooth', '#0284c7'],
        // Restorative
        ['FILL', 'Filling', 'حشوة', 'restorative', 45, 'surface', '#2563eb'],
        ['FILL-COMP', 'Composite (white) filling', 'حشوة تجميلية (بيضاء)', 'restorative', 45, 'surface', '#2563eb'],
        ['FILL-AMAL', 'Amalgam filling', 'حشوة أملغم', 'restorative', 45, 'surface', '#475569'],
        ['FILL-GIC', 'Glass ionomer filling', 'حشوة زجاجية أيونومرية', 'restorative', 30, 'surface', '#60a5fa'],
        ['TEMP', 'Temporary filling', 'حشوة مؤقتة', 'restorative', 20, 'surface', '#a5b4fc'],
        ['CORE', 'Core build-up', 'بناء قلب السن', 'restorative', 45, 'tooth', '#4f46e5'],
        ['INLAY', 'Inlay / onlay', 'حشوة داخلية / خارجية (إنلاي / أونلاي)', 'restorative', 60, 'surface', '#1d4ed8'],
        // Endodontic
        ['RCT', 'Root canal treatment', 'علاج عصب', 'endodontic', 90, 'tooth', '#dc2626'],
        ['RCT-RE', 'Root canal retreatment', 'إعادة علاج العصب', 'endodontic', 120, 'tooth', '#b91c1c'],
        ['PULPOT', 'Pulpotomy', 'بتر اللب', 'endodontic', 45, 'tooth', '#f87171'],
        ['PULPEC', 'Pulpectomy', 'استئصال اللب', 'endodontic', 60, 'tooth', '#ef4444'],
        ['POST', 'Post and core', 'وتد وبناء', 'endodontic', 45, 'tooth', '#991b1b'],
        // Periodontal
        ['DEEP', 'Deep cleaning (root planing)', 'تنظيف عميق (كشط الجذور)', 'periodontal', 60, 'mouth', '#db2777'],
        ['PERIO-SURG', 'Gum surgery', 'جراحة اللثة', 'periodontal', 90, 'mouth', '#be185d'],
        // Surgical
        ['EXT', 'Extraction', 'خلع', 'surgical', 30, 'tooth', '#111827'],
        ['EXT-SURG', 'Surgical extraction', 'خلع جراحي', 'surgical', 60, 'tooth', '#374151'],
        ['WISDOM', 'Wisdom tooth removal', 'خلع ضرس العقل', 'surgical', 60, 'tooth', '#4b5563'],
        ['IMPL', 'Dental implant', 'زراعة سن', 'surgical', 90, 'tooth', '#7c3aed'],
        ['BONE', 'Bone graft', 'تطعيم عظمي', 'surgical', 60, 'tooth', '#a78bfa'],
        // Prosthodontic
        ['CROWN', 'Crown', 'تاج', 'prosthodontic', 60, 'tooth', '#d97706'],
        ['IMPL-CROWN', 'Implant crown', 'تاج على زرعة', 'prosthodontic', 60, 'tooth', '#8b5cf6'],
        ['BRIDGE', 'Bridge (per unit)', 'جسر (لكل وحدة)', 'prosthodontic', 60, 'tooth', '#f59e0b'],
        ['VENEER', 'Veneer', 'قشرة تجميلية (فينير)', 'prosthodontic', 60, 'tooth', '#fbbf24'],
        ['DENT-FULL', 'Complete denture', 'طقم أسنان كامل', 'prosthodontic', 60, 'mouth', '#b45309'],
        ['DENT-PART', 'Partial denture', 'طقم أسنان جزئي', 'prosthodontic', 60, 'mouth', '#92400e'],
        // Orthodontic
        ['ORTHO-CONS', 'Orthodontic consultation', 'استشارة تقويم', 'orthodontic', 30, 'mouth', '#14b8a6'],
        ['BRACES', 'Braces', 'تقويم ثابت', 'orthodontic', 60, 'mouth', '#0d9488'],
        ['ALIGN', 'Clear aligners', 'تقويم شفاف', 'orthodontic', 45, 'mouth', '#0f766e'],
        ['RETAIN', 'Retainer', 'مثبت تقويم', 'orthodontic', 30, 'mouth', '#115e59'],
        // Cosmetic
        ['WHITEN', 'Teeth whitening', 'تبييض الأسنان', 'cosmetic', 60, 'mouth', '#16a34a'],
        ['BOND', 'Cosmetic bonding', 'ترميم تجميلي (بوندينغ)', 'cosmetic', 45, 'surface', '#22c55e'],
        // Other
        ['NIGHTGUARD', 'Night guard', 'واقي الأسنان الليلي', 'other', 30, 'mouth', '#6b7280'],
        ['EMERG', 'Emergency visit', 'زيارة طارئة', 'other', 30, 'mouth', '#e11d48'],
    ];

    /**
     * @return list<array{code: string, name: string, name_ar: string, category: string, default_duration_minutes: int, scope: string, color: string}>
     */
    public static function procedures(): array
    {
        return array_values(array_map(fn (array $row) => [
            'code' => $row[0],
            'name' => $row[1],
            'name_ar' => $row[2],
            'category' => $row[3],
            'default_duration_minutes' => $row[4],
            'scope' => $row[5],
            'color' => $row[6],
        ], self::LIST));
    }

    /**
     * @return int number of procedures added
     */
    public function handle(): int
    {
        $added = 0;

        foreach (self::procedures() as $procedure) {
            $existing = Procedure::where('code', $procedure['code'])->first();
            if ($existing === null) {
                Procedure::create($procedure);
                $added++;
            } elseif ($existing->color === null) {
                $existing->update(['color' => $procedure['color']]);
            }
        }

        return $added;
    }
}
