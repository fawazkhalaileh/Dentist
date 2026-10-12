<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\Publishable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * Before/after pair. Cannot be published without recorded patient consent.
 *
 * @property int $id
 * @property int|null $service_id
 * @property int $before_media_id
 * @property int $after_media_id
 * @property CarbonImmutable|null $consent_confirmed_at
 * @property int|null $consent_confirmed_by
 * @property int $sort_order
 * @property-read Media $beforeMedia
 * @property-read Media $afterMedia
 */
#[Fillable(['service_id', 'before_media_id', 'after_media_id', 'consent_confirmed_at', 'consent_confirmed_by', 'status', 'published_at', 'sort_order'])]
class GalleryItem extends Model
{
    use HasTranslations, Publishable;

    protected function casts(): array
    {
        return [
            'consent_confirmed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Enforced at model level so no code path (CMS, seeder, API) can bypass it.
        static::saving(function (GalleryItem $item) {
            if ($item->status === PublicationStatus::Published && $item->consent_confirmed_at === null) {
                throw new LogicException('Gallery items cannot be published without recorded patient consent.');
            }
        });
    }

    /**
     * @return HasMany<GalleryItemTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(GalleryItemTranslation::class);
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function beforeMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'before_media_id');
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function afterMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'after_media_id');
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
