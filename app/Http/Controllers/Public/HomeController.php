<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Support\Seo;
use App\Support\Site\SiteContent;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('public.home', [
            'treatments' => SiteContent::treatments(8),
            'cases' => SiteContent::smileCases(4),
            'testimonials' => SiteContent::testimonials(),
            'posts' => SiteContent::posts(3),
            'schema' => Seo::clinic(),
        ]);
    }
}
