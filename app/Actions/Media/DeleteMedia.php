<?php

namespace App\Actions\Media;

use App\Models\Media;
use Illuminate\Validation\ValidationException;

class DeleteMedia
{
    /**
     * @throws ValidationException when the image is still used by content
     */
    public function handle(Media $media): void
    {
        if ($media->isInUse()) {
            throw ValidationException::withMessages([
                'media' => __('This image is still used on the website. Remove it from that content first.'),
            ]);
        }

        $media->delete();
    }
}
