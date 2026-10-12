<?php

namespace App\Models;

use App\Enums\Dentition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * FDI reference row (read-only, inserted by migration).
 *
 * @property int $fdi_number
 * @property Dentition $dentition
 * @property int $quadrant
 * @property int $position
 * @property string $arch
 * @property string $name
 */
class Tooth extends Model
{
    protected $table = 'teeth';

    protected $primaryKey = 'fdi_number';

    public $incrementing = false;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'dentition' => Dentition::class,
        ];
    }

    /**
     * @return HasMany<TreatmentItem, $this>
     */
    public function treatmentItems(): HasMany
    {
        return $this->hasMany(TreatmentItem::class, 'tooth_number', 'fdi_number');
    }
}
