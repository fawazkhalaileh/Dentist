<?php

namespace App\Models;

use App\Enums\Role;
use App\Models\Concerns\HasTranslations;
use Database\Factories\StaffProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Professional profile for a staff member (dentists appear on the public site).
 *
 * @property int $id
 * @property int $user_id
 * @property string $slug
 * @property string|null $license_number
 * @property int|null $photo_media_id
 * @property bool $is_public
 * @property int $sort_order
 * @property-read User $user
 * @property-read Media|null $photo
 */
#[Fillable(['user_id', 'slug', 'license_number', 'photo_media_id', 'is_public', 'sort_order'])]
class StaffProfile extends Model
{
    /** @use HasFactory<StaffProfileFactory> */
    use HasFactory, HasTranslations;

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    /**
     * Profiles shown on the public site: marked public, active account, dentist role.
     *
     * @param  Builder<StaffProfile>  $query
     */
    public function scopeShownPublicly(Builder $query): void
    {
        $query->where('is_public', true)
            ->whereHas('user', fn (Builder $u) => $u->where('is_active', true)->havingRole(Role::Dentist));
    }

    /**
     * @return HasMany<StaffProfileTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(StaffProfileTranslation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function photo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'photo_media_id');
    }
}
