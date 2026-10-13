<?php

namespace App\Models;

use Database\Factories\ProcedureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Internal catalogue entry (e.g. composite filling, extraction).
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $name_ar
 * @property string|null $category
 * @property string|null $description
 * @property int $default_duration_minutes
 * @property int|null $price usual price in minor units (fils)
 * @property string $scope tooth|surface|mouth
 * @property string|null $color #rrggbb used on the dental chart
 * @property bool $is_active
 */
#[Fillable(['code', 'name', 'name_ar', 'category', 'description', 'default_duration_minutes', 'price', 'scope', 'color', 'is_active'])]
class Procedure extends Model
{
    /** @use HasFactory<ProcedureFactory> */
    use HasFactory;

    public const CATEGORIES = ['diagnostic', 'preventive', 'restorative', 'endodontic', 'periodontal', 'surgical', 'prosthodontic', 'orthodontic', 'cosmetic', 'other'];

    public const SCOPES = ['mouth', 'tooth', 'surface'];

    /** Chart colour for procedures without their own colour. */
    public const CATEGORY_COLORS = [
        'diagnostic' => '#64748b',
        'preventive' => '#0ea5e9',
        'restorative' => '#2563eb',
        'endodontic' => '#dc2626',
        'periodontal' => '#db2777',
        'surgical' => '#374151',
        'prosthodontic' => '#d97706',
        'orthodontic' => '#0d9488',
        'cosmetic' => '#16a34a',
        'other' => '#6b7280',
    ];

    /**
     * The colour this procedure is drawn with on the dental chart.
     */
    public function displayColor(): string
    {
        return $this->color ?: (self::CATEGORY_COLORS[$this->category ?? 'other'] ?? self::CATEGORY_COLORS['other']);
    }

    public static function categoryLabel(?string $category): string
    {
        return match ($category) {
            'diagnostic' => __('Diagnostic'),
            'preventive' => __('Preventive'),
            'restorative' => __('Restorative'),
            'endodontic' => __('Endodontic'),
            'periodontal' => __('Periodontal'),
            'surgical' => __('Surgical'),
            'prosthodontic' => __('Prosthodontic'),
            'orthodontic' => __('Orthodontic'),
            'cosmetic' => __('Cosmetic'),
            'other' => __('Other'),
            default => '-',
        };
    }

    public static function scopeLabel(string $scope): string
    {
        return match ($scope) {
            'mouth' => __('Whole mouth'),
            'tooth' => __('One tooth'),
            'surface' => __('Tooth surfaces'),
            default => $scope,
        };
    }

    /**
     * The name in the locale (Arabic when available), else the English name.
     */
    public function localizedName(?string $locale = null): string
    {
        return ($locale ?? app()->getLocale()) === 'ar' && $this->name_ar ? $this->name_ar : $this->name;
    }

    /**
     * @return HasMany<TreatmentItem, $this>
     */
    public function treatmentItems(): HasMany
    {
        return $this->hasMany(TreatmentItem::class);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'price' => 'integer',
        ];
    }
}
