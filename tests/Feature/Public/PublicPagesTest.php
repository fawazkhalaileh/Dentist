<?php

namespace Tests\Feature\Public;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pages that must render even with an empty database (empty states).
     *
     * @return array<string, array{string}>
     */
    public static function publicRoutes(): array
    {
        return [
            'home' => ['public.home'],
            'doctor' => ['public.doctor'],
            'about' => ['public.about'],
            'dentists' => ['public.dentists.index'],
            'services' => ['public.services.index'],
            'blog' => ['public.blog.index'],
            'gallery' => ['public.gallery'],
            'testimonials' => ['public.testimonials'],
            'faq' => ['public.faq'],
            'contact' => ['public.contact'],
            'booking' => ['public.booking'],
            'privacy' => ['public.legal.privacy'],
            'terms' => ['public.legal.terms'],
        ];
    }

    #[DataProvider('publicRoutes')]
    public function test_public_pages_render_in_every_locale(string $route): void
    {
        $this->get(route($route, ['locale' => 'en']))
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', escape: false);

        $this->get(route($route, ['locale' => 'ar']))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', escape: false);
    }

    public function test_arabic_pages_use_arabic_interface_text(): void
    {
        $this->get(route('public.contact', ['locale' => 'ar']))
            ->assertSee('أرسل لنا رسالة')
            ->assertSee('احجز موعداً');
    }

    public function test_pages_link_to_their_other_language_version(): void
    {
        $this->get(route('public.services.index', ['locale' => 'en']))
            ->assertSee('<link rel="canonical" href="'.route('public.services.index', ['locale' => 'en']).'" />', escape: false)
            ->assertSee('hreflang="ar" href="'.route('public.services.index', ['locale' => 'ar']).'"', escape: false)
            ->assertDontSee('?view=', escape: false);
    }

    public function test_root_redirects_to_the_preferred_locale(): void
    {
        $this->get('/', ['Accept-Language' => 'ar'])->assertRedirect(route('public.home', ['locale' => 'ar']));
        $this->get('/', ['Accept-Language' => 'fr'])->assertRedirect(route('public.home', ['locale' => 'en']));
    }

    public function test_unsupported_locales_are_not_found(): void
    {
        $this->get('/fr/services')->assertNotFound();
    }

    public function test_unknown_slugs_are_not_found(): void
    {
        $this->get(route('public.services.show', ['locale' => 'en', 'slug' => 'missing']))->assertNotFound();
        $this->get(route('public.blog.show', ['locale' => 'en', 'slug' => 'missing']))->assertNotFound();
        $this->get(route('public.dentists.show', ['locale' => 'en', 'slug' => 'missing']))->assertNotFound();
    }

    public function test_booking_page_is_a_request_form_that_never_shows_the_calendar(): void
    {
        $this->get(route('public.booking', ['locale' => 'en']))
            ->assertOk()
            ->assertSee('This is a request, not a confirmed booking')
            ->assertSee('name="phone"', escape: false)
            ->assertDontSee('Not built yet');
    }

    public function test_public_routes_expose_no_patient_endpoints(): void
    {
        $this->get('/en/patients')->assertNotFound();
        $this->get('/clinic/patients')->assertRedirect(route('login'));
    }
}
