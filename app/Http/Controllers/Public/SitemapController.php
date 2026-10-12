<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Service;
use App\Models\StaffProfile;
use App\Support\Locales;
use App\Support\Seo;
use Illuminate\Http\Response;

/**
 * XML sitemap of published public pages in every locale they exist in,
 * with hreflang alternates. Drafts and unpublished translations are never listed.
 */
class SitemapController extends Controller
{
    /** Fixed pages that exist in every locale. */
    private const STATIC_ROUTES = [
        'public.home', 'public.about', 'public.dentists.index', 'public.services.index',
        'public.blog.index', 'public.gallery', 'public.testimonials', 'public.faq',
        'public.contact', 'public.legal.privacy', 'public.legal.terms',
    ];

    public function __invoke(): Response
    {
        /** @var list<array<string, string>> $groups each: [locale => url] for one page */
        $groups = [];

        foreach (self::STATIC_ROUTES as $name) {
            $group = [];
            foreach (Locales::supported() as $locale) {
                $group[$locale] = route($name, ['locale' => $locale]);
            }
            $groups[] = $group;
        }

        foreach (Service::published()->with('translations')->get() as $service) {
            $groups[] = Seo::alternates($service->translations, 'public.services.show');
        }

        foreach (BlogPost::published()->with('translations')->get() as $post) {
            $groups[] = Seo::alternates($post->translations, 'public.blog.show');
        }

        foreach (BlogCategory::with('translations')->get() as $category) {
            $groups[] = Seo::alternates($category->translations, 'public.blog.category');
        }

        foreach (StaffProfile::shownPublicly()->with('translations')->get() as $profile) {
            $group = [];
            foreach ($profile->translatedLocales() as $locale) {
                $group[$locale] = route('public.dentists.show', ['locale' => $locale, 'slug' => $profile->slug]);
            }
            $groups[] = $group;
        }

        $urls = [];
        foreach (array_filter($groups) as $alternates) {
            foreach ($alternates as $loc) {
                $urls[] = ['loc' => $loc, 'alternates' => $alternates];
            }
        }

        return response()
            ->view('public.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
