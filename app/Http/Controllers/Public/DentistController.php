<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\StaffProfile;
use Illuminate\View\View;

class DentistController extends Controller
{
    public function index(): View
    {
        return view('public.dentists.index', [
            'dentists' => StaffProfile::shownPublicly()->translatedIn()->with(['translations', 'photo'])
                ->orderBy('sort_order')->get(),
        ]);
    }

    public function show(string $slug): View
    {
        $profile = StaffProfile::shownPublicly()->translatedIn()->with(['translations', 'photo'])
            ->where('slug', $slug)->firstOrFail();

        $services = Service::published()->translatedIn()->with('translations')
            ->whereHas('dentists', fn ($q) => $q->whereKey($profile->user_id))
            ->orderBy('sort_order')->get();

        // Profile slugs are shared across locales; only list locales with a translation.
        $alternates = [];
        foreach ($profile->translatedLocales() as $locale) {
            $alternates[$locale] = route('public.dentists.show', ['locale' => $locale, 'slug' => $profile->slug]);
        }

        return view('public.dentists.show', [
            'profile' => $profile,
            'services' => $services,
            'alternates' => $alternates,
        ]);
    }
}
