<?php

namespace Tests\Concerns;

use App\Actions\Content\SaveBlogPost;
use App\Actions\Content\SaveService;
use App\Models\BlogPost;
use App\Models\Service;
use App\Models\User;

/**
 * Builds website content through the same actions the CMS uses.
 */
trait CreatesContent
{
    /**
     * @param  array<string, string>  $titles  locale => title
     * @param  array<string, mixed>  $overrides
     */
    protected function makeService(array $titles = ['en' => 'Fillings'], string $status = 'published', array $overrides = []): Service
    {
        $translations = [];
        foreach ($titles as $locale => $title) {
            $translations[$locale] = ['title' => $title, 'slug' => '', 'summary' => "Summary {$locale}", 'procedure_information' => null, 'seo_title' => null, 'meta_description' => null];
        }

        return app(SaveService::class)->handle([
            'status' => $status,
            'is_bookable' => true,
            'duration_minutes' => 30,
            'sort_order' => 0,
            'featured_media_id' => null,
            'dentist_ids' => [],
            'translations' => $translations,
            'sections' => [],
            'faqs' => [],
            ...$overrides,
        ]);
    }

    /**
     * @param  array<string, string>  $titles
     */
    protected function makePost(array $titles = ['en' => 'Hello'], string $status = 'published', ?string $publishedAt = null): BlogPost
    {
        $translations = [];
        foreach ($titles as $locale => $title) {
            $translations[$locale] = ['title' => $title, 'slug' => '', 'excerpt' => null, 'body' => "Body {$locale}", 'seo_title' => null, 'meta_description' => null];
        }

        return app(SaveBlogPost::class)->handle([
            'status' => $status,
            'blog_category_id' => null,
            'featured_media_id' => null,
            'published_at' => $publishedAt,
            'translations' => $translations,
        ], User::factory()->receptionist()->create());
    }
}
