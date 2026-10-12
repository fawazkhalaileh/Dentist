<?php

namespace App\Actions\Content;

use App\Actions\Content\Concerns\SavesTranslatedContent;
use App\Enums\PublicationStatus;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SaveBlogPost
{
    use SavesTranslatedContent;

    /**
     * @param  array{
     *     status: string, blog_category_id: ?int, featured_media_id: ?int, published_at: ?string,
     *     translations: array<string, array<string, ?string>>
     * }  $data  Validated by App\Livewire\Forms\BlogPostForm
     */
    public function handle(array $data, User $actor, ?BlogPost $post = null): BlogPost
    {
        return DB::transaction(function () use ($data, $actor, $post) {
            $post ??= new BlogPost(['author_id' => $actor->id]);
            $status = PublicationStatus::from($data['status']);

            $post->fill([
                'status' => $status,
                'blog_category_id' => $data['blog_category_id'],
                'featured_media_id' => $data['featured_media_id'],
                'published_at' => $this->publishedAt($status, $post->published_at, $data['published_at']),
            ])->save();

            $this->syncTranslations($post->translations(), $data['translations']);

            return $post->load('translations');
        });
    }
}
