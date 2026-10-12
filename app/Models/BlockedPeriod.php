<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One-off unavailability. dentist_id NULL means the whole clinic is closed.
 *
 * @property int $id
 * @property int|null $dentist_id
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property string|null $reason
 */
#[Fillable(['dentist_id', 'starts_at', 'ends_at', 'reason', 'created_by'])]
class BlockedPeriod extends Model
{
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function dentist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dentist_id');
    }
}
