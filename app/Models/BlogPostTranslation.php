<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $blog_post_id
 * @property string $locale
 * @property string $slug
 * @property string $title
 * @property string|null $excerpt
 * @property string $body Markdown
 * @property string|null $seo_title
 * @property string|null $meta_description
 * @property-read BlogPost $post
 */
#[Fillable(['blog_post_id', 'locale', 'slug', 'title', 'excerpt', 'body', 'seo_title', 'meta_description'])]
class BlogPostTranslation extends Model
{
    /**
     * @return BelongsTo<BlogPost, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class, 'blog_post_id');
    }
}
