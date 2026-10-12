<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\GalleryItem;
use App\Models\Testimonial;
use App\Support\Seo;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

/**
 * Simple published-content listings: gallery, testimonials, FAQ.
 */
class PublicContentController extends Controller
{
    public function gallery(): View
    {
        return view('public.gallery', [
            // published() already implies consent (enforced when saving); the extra check is defence in depth.
            'items' => GalleryItem::published()->whereNotNull('consent_confirmed_at')->translatedIn()
                ->with(['translations', 'beforeMedia', 'afterMedia'])->orderBy('sort_order')->get(),
        ]);
    }

    public function testimonials(): View
    {
        return view('public.testimonials', [
            'testimonials' => Testimonial::published()->whereNotNull('consent_confirmed_at')
                ->where('locale', App::getLocale())->orderBy('sort_order')->get(),
        ]);
    }

    public function faq(): View
    {
        $faqs = Faq::published()->where('locale', App::getLocale())->orderBy('sort_order')->get();

        return view('public.faq', [
            'faqs' => $faqs,
            'schema' => Seo::faqPage(array_values($faqs->map(fn (Faq $f) => ['question' => $f->question, 'answer' => $f->answer])->all())),
        ]);
    }
}
