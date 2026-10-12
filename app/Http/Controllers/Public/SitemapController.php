<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Support\Locales;
use Illuminate\Http\Response;

/**
 * XML sitemap of public pages in every locale. Phase 2 adds published
 * services and blog posts from the database.
 */
class SitemapController extends Controller
{
    /**
     * Static public pages. Placeholder pages are listed because their URLs are final.
     *
     * @var list<string>
     */
    private const STATIC_ROUTES = [
        'public.home', 'public.about', 'public.dentists.index', 'public.services.index',
        'public.blog.index', 'public.gallery', 'public.testimonials', 'public.faq',
        'public.contact', 'public.booking', 'public.legal.privacy', 'public.legal.terms',
    ];

    public function __invoke(): Response
    {
        $urls = [];

        foreach (self::STATIC_ROUTES as $name) {
            $alternates = [];

            foreach (Locales::supported() as $locale) {
                $alternates[$locale] = route($name, ['locale' => $locale]);
            }

            foreach ($alternates as $loc) {
                $urls[] = ['loc' => $loc, 'alternates' => $alternates];
            }
        }

        return response()
            ->view('public.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
