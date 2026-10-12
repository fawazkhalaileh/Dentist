<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactMessageRequest;
use App\Models\ContactMessage;
use App\Support\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('public.contact', ['schema' => Seo::clinic()]);
    }

    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        ContactMessage::create([
            ...$request->safe()->only(['name', 'email', 'phone', 'message']),
            'locale' => App::getLocale(),
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('public.contact')->with('status', 'contact-sent');
    }
}
