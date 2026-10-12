<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $page_id
 * @property string $locale
 * @property string $title
 * @property string|null $body Markdown
 * @property string|null $seo_title
 * @property string|null $meta_description
 */
#[Fillable(['page_id', 'locale', 'title', 'body', 'seo_title', 'meta_description'])]
class PageTranslation extends Model {}
