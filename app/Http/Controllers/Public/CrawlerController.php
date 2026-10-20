<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Support\ClinicDetails;
use App\Support\Site\SiteContent;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;

/**
 * Plain-text files for crawlers: robots.txt for search engines and llms.txt, a
 * short factual summary for AI assistants (https://llmstxt.org). Both are built
 * from the clinic's real details and published content only, never from samples.
 */
class CrawlerController extends Controller
{
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            // Staff and patient areas are private; there is nothing to index there.
            'Disallow: /clinic',
            'Disallow: /portal',
            'Disallow: /dashboard',
            'Disallow: /settings',
            'Allow: /',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function llms(): Response
    {
        App::setLocale('en');

        $name = ClinicDetails::name();
        $facts = array_filter([
            'Doctor' => SiteContent::doctorName(),
            'Address' => ClinicDetails::get('address', 'en'),
            'Opening hours' => ClinicDetails::get('opening_hours', 'en'),
            'Phone' => ClinicDetails::get('phone'),
            'WhatsApp' => ClinicDetails::get('whatsapp'),
            'Email' => ClinicDetails::get('email'),
            'Languages' => 'Arabic and English',
            'Instagram' => config('site.social.instagram'),
        ]);

        $services = Service::published()->translatedIn('en')->with('translations')->orderBy('sort_order')->get()
            ->map(function (Service $service) {
                $t = $service->translations->firstWhere('locale', 'en');

                return $t ? '- ['.$t->title.']('.route('public.services.show', ['locale' => 'en', 'slug' => $t->slug]).')'.($t->summary ? ': '.$t->summary : '') : null;
            })->filter();

        $out = ["# {$name}", '', "> {$name} is a dental clinic in Amman, Jordan. Patients can request an appointment online; the clinic calls back to confirm a time.", ''];
        foreach ($facts as $label => $value) {
            $out[] = "- {$label}: ".str_replace("\n", '; ', $value);
        }

        $out[] = '';
        $out[] = '## Treatments';
        $out[] = '';
        if ($services->isNotEmpty()) {
            array_push($out, ...$services->all());
        } else {
            $out[] = 'The clinic is finalising its treatment list. See '.route('public.services.index', ['locale' => 'en']).' or contact the clinic.';
        }

        $out[] = '';
        $out[] = '## Pages';
        $out[] = '';
        foreach ([
            'public.home' => 'Home', 'public.about' => 'About the clinic', 'public.doctor' => 'Meet the doctor',
            'public.services.index' => 'Treatments', 'public.gallery' => 'Smile gallery (before and after, shared with consent)',
            'public.booking' => 'Request an appointment', 'public.contact' => 'Contact and opening hours', 'public.faq' => 'Frequently asked questions',
        ] as $route => $label) {
            $out[] = "- [{$label}](".route($route, ['locale' => 'en']).') (Arabic: '.route($route, ['locale' => 'ar']).')';
        }

        return response(implode("\n", $out)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
