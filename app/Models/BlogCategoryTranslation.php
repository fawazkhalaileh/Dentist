<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $blog_category_id
 * @property string $locale
 * @property string $slug
 * @property string $name
 */
#[Fillable(['blog_category_id', 'locale', 'slug', 'name'])]
class BlogCategoryTranslation extends Model
{
    /**
     * @return BelongsTo<BlogCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }
}
