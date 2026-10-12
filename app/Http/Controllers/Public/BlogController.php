<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogCategoryTranslation;
use App\Models\BlogPost;
use App\Models\BlogPostTranslation;
use App\Support\Seo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

class BlogController extends Controller
{
    private const PER_PAGE = 9;

    public function index(): View
    {
        return view('public.blog.index', [
            'posts' => $this->postsQuery()->paginate(self::PER_PAGE),
            'categories' => $this->categories(),
            'currentCategory' => null,
        ]);
    }

    public function category(string $slug): View
    {
        $translation = BlogCategoryTranslation::where('locale', App::getLocale())->where('slug', $slug)->firstOrFail();
        /** @var BlogCategory $category */
        $category = BlogCategory::with('translations')->findOrFail($translation->blog_category_id);

        return view('public.blog.index', [
            'posts' => $this->postsQuery()->where('blog_category_id', $category->id)->paginate(self::PER_PAGE),
            'categories' => $this->categories(),
            'currentCategory' => $translation,
            'alternates' => Seo::alternates($category->translations, 'public.blog.category'),
        ]);
    }

    public function show(string $slug): View
    {
        $translation = BlogPostTranslation::where('locale', App::getLocale())->where('slug', $slug)->firstOrFail();

        /** @var BlogPost $post */
        $post = BlogPost::published()->with(['translations', 'featuredMedia', 'author', 'category.translations'])
            ->findOrFail($translation->blog_post_id);

        $schema = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $translation->title,
            'description' => $translation->meta_description ?? $translation->excerpt,
            'datePublished' => $post->published_at?->toAtomString(),
            'dateModified' => $post->updated_at?->toAtomString(),
            'inLanguage' => $translation->locale,
            'image' => $post->featuredMedia?->url(),
            'author' => ['@type' => 'Person', 'name' => $post->author->name],
            'publisher' => ['@type' => 'Organization', 'name' => config('clinic.name')],
        ]);

        return view('public.blog.show', [
            'post' => $post,
            'translation' => $translation,
            'alternates' => Seo::alternates($post->translations, 'public.blog.show'),
            'schema' => $schema,
        ]);
    }

    /**
     * @return Builder<BlogPost>
     */
    private function postsQuery(): Builder
    {
        return BlogPost::published()->translatedIn()
            ->with(['translations', 'featuredMedia', 'category.translations'])
            ->latest('published_at');
    }

    /**
     * @return Collection<int, BlogCategory>
     */
    private function categories()
    {
        return BlogCategory::translatedIn()
            ->whereHas('posts', fn (Builder $q) => $q->published()->translatedIn())
            ->with('translations')->orderBy('sort_order')->get();
    }
}
