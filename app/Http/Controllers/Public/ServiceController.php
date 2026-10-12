<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceFaq;
use App\Models\ServiceTranslation;
use App\Models\StaffProfile;
use App\Support\Seo;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        return view('public.services.index', [
            'services' => Service::published()->translatedIn()->with(['translations', 'featuredMedia'])
                ->orderBy('sort_order')->get(),
        ]);
    }

    public function show(string $slug): View
    {
        $locale = App::getLocale();

        $translation = ServiceTranslation::where('locale', $locale)->where('slug', $slug)->firstOrFail();

        /** @var Service $service */
        $service = Service::published()->with(['translations', 'featuredMedia'])->findOrFail($translation->service_id);

        $faqs = $service->faqs()->where('locale', $locale)->get();

        $dentists = StaffProfile::shownPublicly()->translatedIn()->with(['translations', 'photo'])
            ->whereIn('user_id', $service->dentists()->pluck('users.id'))
            ->orderBy('sort_order')->get();

        return view('public.services.show', [
            'service' => $service,
            'translation' => $translation,
            'sections' => $service->sections()->where('locale', $locale)->get(),
            'faqs' => $faqs,
            'dentists' => $dentists,
            'alternates' => Seo::alternates($service->translations, 'public.services.show'),
            'schema' => Seo::faqPage(array_values($faqs->map(fn (ServiceFaq $f) => ['question' => $f->question, 'answer' => $f->answer])->all())),
        ]);
    }
}
