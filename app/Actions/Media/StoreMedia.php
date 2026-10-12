<?php

namespace App\Actions\Media;

use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

/**
 * Turns an uploaded image into two optimised WebP files on the public disk.
 *
 * - Applies EXIF orientation, then re-encodes, which drops EXIF/GPS metadata.
 * - The original upload is not kept.
 */
class StoreMedia
{
    private const DISK = 'public';

    private const QUALITY = 80;

    /**
     * @param  array<string, string|null>  $alt  Alt text per locale
     */
    public function handle(UploadedFile $file, User $uploader, array $alt = []): Media
    {
        $manager = new ImageManager(new Driver);
        $directory = 'media/'.now()->format('Y/m');
        $name = (string) Str::uuid();

        $full = $manager->decodePath($file->getRealPath())->orient()->scaleDown(width: Media::FULL_WIDTH);
        $fullEncoded = $full->encode(new WebpEncoder(quality: self::QUALITY, strip: true));

        $thumb = $manager->decodePath($file->getRealPath())->orient()->scaleDown(width: Media::THUMB_WIDTH);
        $thumbEncoded = $thumb->encode(new WebpEncoder(quality: self::QUALITY, strip: true));

        $path = "{$directory}/{$name}.webp";
        $thumbPath = "{$directory}/{$name}-thumb.webp";

        Storage::disk(self::DISK)->put($path, (string) $fullEncoded);
        Storage::disk(self::DISK)->put($thumbPath, (string) $thumbEncoded);

        return Media::create([
            'disk' => self::DISK,
            'path' => $path,
            'thumb_path' => $thumbPath,
            'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
            'mime_type' => 'image/webp',
            'width' => $full->width(),
            'height' => $full->height(),
            'size_bytes' => strlen((string) $fullEncoded),
            'alt' => array_filter($alt, fn ($v) => filled($v)) ?: null,
            'uploaded_by' => $uploader->id,
        ]);
    }

    /**
     * Validation rules for an image upload field.
     *
     * @return list<string>
     */
    public static function rules(): array
    {
        // 8 MB, common photo formats only. SVG is excluded (it can contain scripts).
        return ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:min_width=200,min_height=200'];
    }
}
