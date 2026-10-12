<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Public website service. Localised text lives in ServiceTranslation;
 * sections and FAQs are stored per locale.
 *
 * @property int $id
 * @property int|null $featured_media_id
 * @property bool $is_bookable
 * @property int $duration_minutes
 * @property int $sort_order
 * @property-read Media|null $featuredMedia
 */
#[Fillable(['status', 'featured_media_id', 'is_bookable', 'duration_minutes', 'sort_order', 'published_at'])]
class Service extends Model
{
    use HasTranslations, Publishable;

    protected function casts(): array
    {
        return [
            'is_bookable' => 'boolean',
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
     * @return HasMany<ServiceSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(ServiceSection::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<ServiceFaq, $this>
     */
    public function faqs(): HasMany
    {
        return $this->hasMany(ServiceFaq::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function dentists(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'service_dentist', 'service_id', 'dentist_id');
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
