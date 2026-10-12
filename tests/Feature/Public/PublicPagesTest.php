<?php

namespace Tests\Feature\Public;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, array<string, string>}>
     */
    public static function publicRoutes(): array
    {
        return [
            'home' => ['public.home', []],
            'about' => ['public.about', []],
            'dentists' => ['public.dentists.index', []],
            'dentist' => ['public.dentists.show', ['slug' => 'example']],
            'services' => ['public.services.index', []],
            'service' => ['public.services.show', ['slug' => 'example']],
            'blog' => ['public.blog.index', []],
            'blog category' => ['public.blog.category', ['slug' => 'example']],
            'article' => ['public.blog.show', ['slug' => 'example']],
            'gallery' => ['public.gallery', []],
            'testimonials' => ['public.testimonials', []],
            'faq' => ['public.faq', []],
            'contact' => ['public.contact', []],
            'booking' => ['public.booking', []],
            'privacy' => ['public.legal.privacy', []],
            'terms' => ['public.legal.terms', []],
        ];
    }

    /**
     * @param  array<string, string>  $params
     */
    #[DataProvider('publicRoutes')]
    public function test_public_pages_render_in_every_locale(string $route, array $params): void
    {
        $this->get(route($route, [...$params, 'locale' => 'en']))
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', escape: false);

        $this->get(route($route, [...$params, 'locale' => 'ar']))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', escape: false);
    }

    public function test_pages_link_to_their_other_language_version(): void
    {
        $this->get(route('public.services.show', ['locale' => 'en', 'slug' => 'example']))
            ->assertSee('<link rel="canonical" href="'.route('public.services.show', ['locale' => 'en', 'slug' => 'example']).'" />', escape: false)
            ->assertSee('hreflang="ar" href="'.route('public.services.show', ['locale' => 'ar', 'slug' => 'example']).'"', escape: false)
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

    public function test_placeholder_pages_say_they_are_not_built(): void
    {
        $this->get(route('public.services.index', ['locale' => 'en']))
            ->assertSee('Not built yet');
    }

    public function test_sitemap_lists_pages_in_both_locales(): void
    {
        $this->get(route('sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('public.about', ['locale' => 'en']), escape: false)
            ->assertSee(route('public.about', ['locale' => 'ar']), escape: false);
    }

    public function test_public_routes_expose_no_patient_endpoints(): void
    {
        $this->get('/en/patients')->assertNotFound();
        $this->get('/clinic/patients')->assertRedirect(route('login'));
    }
}
