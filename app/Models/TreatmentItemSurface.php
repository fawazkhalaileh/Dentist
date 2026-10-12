<?php

namespace App\Models;

use App\Enums\ToothSurface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $treatment_item_id
 * @property ToothSurface $surface
 */
#[Fillable(['treatment_item_id', 'surface'])]
class TreatmentItemSurface extends Model
{
    // Composite key (treatment_item_id, surface): rows are only inserted, never updated.
    protected $primaryKey = null;

    public $incrementing = false;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'surface' => ToothSurface::class,
        ];
    }
}
