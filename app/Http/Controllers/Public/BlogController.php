<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogCategoryTranslation;
use App\Models\BlogPost;
use App\Models\BlogPostTranslation;
use App\Support\ClinicDetails;
use App\Support\Seo;
use App\Support\Site\SiteContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

class BlogController extends Controller
{
    private const PER_PAGE = 9;

    public function index(Request $request): View
    {
        $search = $this->search($request);

        if (SiteContent::usingSamplePosts()) {
            return $this->sampleIndex(null, $search);
        }

        $posts = $this->postsQuery();
        if ($search !== null) {
            $posts->whereHas('translations', fn (Builder $q) => $q->where('locale', App::getLocale())
                ->where(fn (Builder $q) => $q->where('title', 'like', '%'.$this->escapeLike($search).'%')
                    ->orWhere('excerpt', 'like', '%'.$this->escapeLike($search).'%')));
        }

        return view('public.blog.index', [
            'posts' => $posts->paginate(self::PER_PAGE)->withQueryString(),
            'categories' => $this->categories(),
            'currentCategory' => null,
            'search' => $search,
            'sample' => false,
        ]);
    }

    public function category(Request $request, string $slug): View
    {
        $translation = BlogCategoryTranslation::where('locale', App::getLocale())->where('slug', $slug)->first();

        if ($translation === null) {
            abort_unless(SiteContent::usingSamplePosts() && array_key_exists($slug, SiteContent::sampleCategories()), 404);

            return $this->sampleIndex($slug, $this->search($request));
        }

        /** @var BlogCategory $category */
        $category = BlogCategory::with('translations')->findOrFail($translation->blog_category_id);

        return view('public.blog.index', [
            'posts' => $this->postsQuery()->where('blog_category_id', $category->id)->paginate(self::PER_PAGE),
            'categories' => $this->categories(),
            'currentCategory' => $translation,
            'search' => null,
            'sample' => false,
            'alternates' => Seo::alternates($category->translations, 'public.blog.category'),
        ]);
    }

    public function show(string $slug): View
    {
        $translation = BlogPostTranslation::where('locale', App::getLocale())->where('slug', $slug)->first();

        if ($translation === null) {
            $sample = SiteContent::usingSamplePosts() ? SiteContent::samplePost($slug) : null;
            abort_if($sample === null, 404);

            return view('public.blog.sample', [
                'article' => $sample,
                'related' => SiteContent::samplePosts($sample['category_key'])->reject(fn (array $p) => $p['title'] === $sample['title'])
                    ->concat(SiteContent::samplePosts())->unique('title')->reject(fn (array $p) => $p['title'] === $sample['title'])->take(3)->values(),
            ]);
        }

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
            'publisher' => ['@type' => 'Organization', 'name' => ClinicDetails::name()],
        ]);

        $related = $this->postsQuery()->whereKeyNot($post->id)
            ->when($post->blog_category_id, fn (Builder $q) => $q->orderByRaw('blog_category_id = ? desc', [$post->blog_category_id]))
            ->limit(3)->get();

        return view('public.blog.show', [
            'post' => $post,
            'related' => $related,
            'translation' => $translation,
            'alternates' => Seo::alternates($post->translations, 'public.blog.show'),
            'schema' => $schema,
        ]);
    }

    /**
     * Sample articles stand in until the clinic publishes its own.
     */
    private function sampleIndex(?string $categoryKey, ?string $search): View
    {
        return view('public.blog.index', [
            'posts' => SiteContent::samplePosts($categoryKey, $search),
            'categories' => SiteContent::sampleCategories(),
            'currentCategory' => $categoryKey,
            'search' => $search,
            'sample' => true,
        ]);
    }

    private function search(Request $request): ?string
    {
        $term = trim(mb_substr((string) $request->query('q', ''), 0, 100));

        return $term === '' ? null : $term;
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
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
