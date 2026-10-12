<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $sort_order
 */
#[Fillable(['sort_order'])]
class BlogCategory extends Model
{
    use HasTranslations;

    /**
     * @return HasMany<BlogCategoryTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(BlogCategoryTranslation::class);
    }

    /**
     * @return HasMany<BlogPost, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }
}
