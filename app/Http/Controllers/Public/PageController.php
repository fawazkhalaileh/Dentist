<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageTranslation;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Fixed editable pages (about, privacy, terms). The route sets the page key.
 */
class PageController extends Controller
{
    public function __invoke(Request $request): View
    {
        $key = (string) $request->route()?->defaults['page'];
        abort_unless(array_key_exists($key, Page::KEYS), 404);

        $page = Page::published()->with('translations')->where('key', $key)->first();
        /** @var PageTranslation|null $translation */
        $translation = $page?->translation();

        return view('public.page', [
            'key' => $key,
            'translation' => $translation,
            'fallbackTitle' => match ($key) {
                'about' => __('About the clinic'),
                'privacy' => __('Privacy policy'),
                'terms' => __('Terms of use'),
            },
        ]);
    }
}
