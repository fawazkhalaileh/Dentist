<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceFaq;
use App\Models\ServiceTranslation;
use App\Models\StaffProfile;
use App\Support\Seo;
use App\Support\Site\SiteContent;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        return view('public.services.index', [
            'treatments' => SiteContent::treatments(),
        ]);
    }

    public function show(string $slug): View
    {
        $locale = App::getLocale();

        $translation = ServiceTranslation::where('locale', $locale)->where('slug', $slug)->first();

        if ($translation === null) {
            // Until the clinic publishes its own services, the sample treatments have general information pages.
            $sample = SiteContent::usingSampleTreatments() ? SiteContent::sampleTreatment($slug) : null;
            abort_if($sample === null, 404);

            return view('public.services.sample', [
                'treatment' => $sample,
                'related' => SiteContent::treatments()->reject(fn (array $t) => $t['title'] === $sample['title'])->take(3),
            ]);
        }

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
            'related' => SiteContent::treatments()->reject(fn (array $t) => $t['title'] === $translation->title)->take(3),
            'alternates' => Seo::alternates($service->translations, 'public.services.show'),
            'schema' => Seo::faqPage(array_values($faqs->map(fn (ServiceFaq $f) => ['question' => $f->question, 'answer' => $f->answer])->all())),
        ]);
    }
}
