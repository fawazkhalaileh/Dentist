<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Service;
use App\Models\StaffProfile;
use App\Models\Testimonial;
use App\Support\Seo;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $locale = App::getLocale();

        return view('public.home', [
            'services' => Service::published()->translatedIn()->with(['translations', 'featuredMedia'])
                ->orderBy('sort_order')->limit(6)->get(),
            'dentists' => StaffProfile::shownPublicly()->translatedIn()->with(['translations', 'photo'])
                ->orderBy('sort_order')->limit(4)->get(),
            'testimonials' => Testimonial::published()->where('locale', $locale)
                ->orderBy('sort_order')->limit(3)->get(),
            'posts' => BlogPost::published()->translatedIn()->with(['translations', 'featuredMedia'])
                ->latest('published_at')->limit(3)->get(),
            'schema' => Seo::clinic(),
        ]);
    }
}
