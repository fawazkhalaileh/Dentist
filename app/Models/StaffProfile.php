<?php

namespace App\Models;

use Database\Factories\StaffProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Professional profile for a staff member (dentists appear on the public site).
 *
 * @property int $id
 * @property int $user_id
 * @property string $slug
 * @property string|null $title
 * @property string|null $specialty
 * @property string|null $license_number
 * @property string|null $bio
 * @property string|null $photo_path
 * @property bool $is_public
 * @property int $sort_order
 * @property-read User $user
 */
#[Fillable(['user_id', 'slug', 'title', 'specialty', 'license_number', 'bio', 'photo_path', 'is_public', 'sort_order'])]
class StaffProfile extends Model
{
    /** @use HasFactory<StaffProfileFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
