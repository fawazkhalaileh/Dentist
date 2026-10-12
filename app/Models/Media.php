<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;

/**
 * Public website image, stored as optimised WebP (full + thumbnail).
 *
 * @property int $id
 * @property string $disk
 * @property string $path
 * @property string $thumb_path
 * @property string $original_name
 * @property string $mime_type
 * @property int $width
 * @property int $height
 * @property int $size_bytes
 * @property array<string, string|null>|null $alt
 * @property int|null $uploaded_by
 */
#[Fillable(['disk', 'path', 'thumb_path', 'original_name', 'mime_type', 'width', 'height', 'size_bytes', 'alt', 'uploaded_by'])]
class Media extends Model
{
    /** Width of the thumbnail variant, used in srcset. */
    public const THUMB_WIDTH = 480;

    /** Maximum width of the full variant. */
    public const FULL_WIDTH = 1600;

    protected function casts(): array
    {
        return [
            'alt' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // Remove the files once the row is gone (deletion is blocked while the image is in use).
        static::deleted(function (Media $media) {
            Storage::disk($media->disk)->delete([$media->path, $media->thumb_path]);
        });
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function thumbUrl(): string
    {
        return Storage::disk($this->disk)->url($this->thumb_path);
    }

    public function altFor(?string $locale = null): string
    {
        $locale ??= App::getLocale();

        return (string) ($this->alt[$locale] ?? '');
    }

    /**
     * Whether any content still references this image.
     */
    public function isInUse(): bool
    {
        return Service::where('featured_media_id', $this->id)->exists()
            || StaffProfile::where('photo_media_id', $this->id)->exists()
            || BlogPost::where('featured_media_id', $this->id)->exists()
            || GalleryItem::where('before_media_id', $this->id)->orWhere('after_media_id', $this->id)->exists();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
