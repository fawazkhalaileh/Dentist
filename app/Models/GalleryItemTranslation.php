<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $gallery_item_id
 * @property string $locale
 * @property string $title
 * @property string|null $description
 */
#[Fillable(['gallery_item_id', 'locale', 'title', 'description'])]
class GalleryItemTranslation extends Model {}
