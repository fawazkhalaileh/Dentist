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
