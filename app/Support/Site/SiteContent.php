<?php

namespace App\Support\Site;

use App\Models\BlogPost;
use App\Models\GalleryItem;
use App\Models\Media;
use App\Models\Service;
use App\Models\Testimonial;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

/**
 * Presentation data for the website. Published CMS records always win; sample
 * content (clearly flagged) only fills a section the clinic has not published yet.
 *
 * @phpstan-type Treatment array{title: string, summary: ?string, url: string, icon: string, media: ?Media, sample: bool}
 * @phpstan-type Quote array{author: string, body: string, sample: bool}
 * @phpstan-type Post array{title: string, excerpt: ?string, url: string, category: ?string, date: ?CarbonImmutable, media: ?Media, photo: ?string, sample: bool}
 */
final class SiteContent
{
    /**
     * @return Collection<int, Treatment>
     */
    public static function treatments(?int $limit = null): Collection
    {
        $services = Service::published()->translatedIn()->with(['translations', 'featuredMedia'])
            ->orderBy('sort_order')->when($limit, fn ($q) => $q->limit($limit))->get();

        $icons = array_column(SampleContent::treatments(), 'icon');
        $locale = self::locale();
        $items = [];

        foreach ($services as $i => $service) {
            $t = $service->translations->firstWhere('locale', App::getLocale());
            if ($t === null) {
                continue;
            }
            $items[] = [
                'title' => $t->title,
                'summary' => $t->summary,
                'url' => route('public.services.show', ['slug' => $t->slug]),
                'icon' => $icons[$i % count($icons)],
                'media' => $service->featuredMedia,
                'sample' => false,
            ];
        }

        if ($items === []) {
            foreach (SampleContent::treatments() as $item) {
                $items[] = [
                    'title' => $item[$locale]['title'],
                    'summary' => $item[$locale]['summary'],
                    'url' => route('public.services.index').'#'.$item['key'],
                    'icon' => $item['icon'],
                    'media' => null,
                    'sample' => true,
                ];
            }
        }

        return collect($limit ? array_slice($items, 0, $limit) : $items);
    }

    /**
     * @return Collection<int, Quote>
     */
    public static function testimonials(int $limit = 6): Collection
    {
        $items = [];
        $real = Testimonial::published()->whereNotNull('consent_confirmed_at')
            ->where('locale', App::getLocale())->orderBy('sort_order')->limit($limit)->get();

        foreach ($real as $t) {
            $items[] = ['author' => $t->author_name, 'body' => $t->body, 'sample' => false];
        }

        if ($items === []) {
            $locale = self::locale();
            foreach (SampleContent::testimonials() as $t) {
                $items[] = ['author' => $t[$locale]['author'], 'body' => $t[$locale]['body'], 'sample' => true];
            }
        }

        return collect($items);
    }

    /**
     * @return Collection<int, Post>
     */
    public static function posts(int $limit = 3): Collection
    {
        $items = [];
        $real = BlogPost::published()->translatedIn()->with(['translations', 'featuredMedia', 'category.translations'])
            ->latest('published_at')->limit($limit)->get();

        foreach ($real as $post) {
            $t = $post->translations->firstWhere('locale', App::getLocale());
            if ($t === null) {
                continue;
            }
            $items[] = [
                'title' => $t->title,
                'excerpt' => $t->excerpt,
                'url' => route('public.blog.show', ['slug' => $t->slug]),
                'category' => $post->category?->translations->firstWhere('locale', App::getLocale())?->name,
                'date' => $post->published_at,
                'media' => $post->featuredMedia,
                'photo' => null,
                'sample' => false,
            ];
        }

        if ($items === []) {
            $locale = self::locale();
            foreach (array_slice(SampleContent::posts(), 0, $limit) as $p) {
                $items[] = [
                    'title' => $p[$locale]['title'],
                    'excerpt' => $p[$locale]['excerpt'],
                    'url' => route('public.blog.index'),
                    'category' => $p[$locale]['category'],
                    'date' => CarbonImmutable::parse($p['date']),
                    'media' => null,
                    'photo' => $p['image'],
                    'sample' => true,
                ];
            }
        }

        return collect($items);
    }

    /**
     * Published before/after pairs (consent is enforced by the model and re-checked here).
     *
     * @return Collection<int, GalleryItem>
     */
    public static function smileCases(?int $limit = null): Collection
    {
        return GalleryItem::published()->whereNotNull('consent_confirmed_at')->translatedIn()
            ->with(['translations', 'beforeMedia', 'afterMedia', 'service.translations'])
            ->orderBy('sort_order')->when($limit, fn ($q) => $q->limit($limit))->get();
    }

    public static function doctorName(): string
    {
        return (string) config('site.doctor_name.'.self::locale(), 'Dr. Mohammed Amara');
    }

    public static function doctorShortName(): string
    {
        return (string) config('site.doctor_short_name.'.self::locale(), 'Dr. Amara');
    }

    /**
     * @return 'en'|'ar'
     */
    private static function locale(): string
    {
        return App::getLocale() === 'ar' ? 'ar' : 'en';
    }
}
