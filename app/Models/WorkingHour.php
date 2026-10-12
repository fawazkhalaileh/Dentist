<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $dentist_id
 * @property int $weekday 0 = Sunday ... 6 = Saturday
 * @property string $starts_at
 * @property string $ends_at
 */
#[Fillable(['dentist_id', 'weekday', 'starts_at', 'ends_at'])]
class WorkingHour extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function dentist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dentist_id');
    }
}
