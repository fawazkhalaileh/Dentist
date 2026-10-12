<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Public website service. Localised text lives in ServiceTranslation.
 *
 * @property int $id
 * @property PublicationStatus $status
 * @property string|null $featured_image_path
 * @property bool $is_bookable
 * @property int $duration_minutes
 * @property int $sort_order
 * @property Carbon|null $published_at
 */
#[Fillable(['status', 'featured_image_path', 'is_bookable', 'duration_minutes', 'sort_order', 'published_at'])]
class Service extends Model
{
    protected function casts(): array
    {
        return [
            'status' => PublicationStatus::class,
            'is_bookable' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<ServiceTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(ServiceTranslation::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function dentists(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'service_dentist', 'service_id', 'dentist_id');
    }
}
