<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use App\Models\Concerns\Publishable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * A patient testimonial in one language. Publishing requires recorded consent.
 *
 * @property int $id
 * @property string $locale
 * @property string $author_name
 * @property string $body
 * @property int|null $rating
 * @property int|null $service_id
 * @property CarbonImmutable|null $consent_confirmed_at
 * @property int|null $consent_confirmed_by
 * @property int $sort_order
 */
#[Fillable(['locale', 'author_name', 'body', 'rating', 'service_id', 'consent_confirmed_at', 'consent_confirmed_by', 'status', 'published_at', 'sort_order'])]
class Testimonial extends Model
{
    use Publishable;

    protected function casts(): array
    {
        return [
            'consent_confirmed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Testimonial $testimonial) {
            if ($testimonial->status === PublicationStatus::Published && $testimonial->consent_confirmed_at === null) {
                throw new LogicException('Testimonials cannot be published without recorded consent.');
            }
        });
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
