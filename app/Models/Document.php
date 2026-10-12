<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Uploaded patient file stored on the private clinical disk.
 *
 * @property int $id
 * @property int $patient_id
 * @property int $uploaded_by
 * @property string|null $category
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size_bytes
 * @property-read Patient $patient
 */
#[Fillable(['patient_id', 'documentable_type', 'documentable_id', 'uploaded_by', 'category', 'disk', 'path', 'original_name', 'mime_type', 'size_bytes'])]
class Document extends Model
{
    use SoftDeletes;

    public const CATEGORIES = ['xray', 'photo', 'consent', 'referral', 'lab', 'other'];

    /** File types accepted for upload (checked against the file's content). */
    public const MIMES = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];

    public const MAX_KILOBYTES = 10240;

    public static function categoryLabel(?string $category): string
    {
        return match ($category) {
            'xray' => __('X-ray'),
            'photo' => __('Clinical photo'),
            'consent' => __('Consent form'),
            'referral' => __('Referral letter'),
            'lab' => __('Lab report'),
            default => __('Other'),
        };
    }

    /**
     * PDFs and images can be shown in the browser; anything else downloads.
     */
    public function isViewableInline(): bool
    {
        return in_array($this->mime_type, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }
}
