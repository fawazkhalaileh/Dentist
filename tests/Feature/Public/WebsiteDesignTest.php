<?php

namespace Tests\Feature\Public;

use App\Models\BlogPost;
use App\Models\Service;
use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesContent;
use Tests\TestCase;

class WebsiteDesignTest extends TestCase
{
    use CreatesContent, RefreshDatabase;

    public function test_the_homepage_shows_labelled_samples_until_real_content_is_published(): void
    {
        $this->get('/en')
            ->assertOk()
            ->assertSee('Your Smile,')
            ->assertSee('Teeth Whitening')
            ->assertSee('Sample treatments, to be confirmed by the clinic')
            ->assertSee('Sample testimonials for design only, not real reviews')
            ->assertSee('Sample article')
            ->assertSee('Placeholder until consent is confirmed');

        // Samples are presentation only: nothing is written to the database.
        $this->assertSame(0, Service::count());
        $this->assertSame(0, Testimonial::count());
        $this->assertSame(0, BlogPost::count());
    }

    public function test_published_services_replace_the_sample_treatments(): void
    {
        $this->makeService(['en' => 'Gum treatment']);

        $this->get('/en')
            ->assertSee('Gum treatment')
            ->assertSee('/en/services/gum-treatment', escape: false)
            ->assertDontSee('Sample treatments, to be confirmed by the clinic')
            ->assertDontSee('Teeth Whitening');
    }

    public function test_the_arabic_homepage_is_translated_and_right_to_left(): void
    {
        $this->get('/ar')
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', escape: false)
            ->assertSee('ابتسامتك،')
            ->assertSee('احجز موعداً')
            ->assertSee('تبييض الأسنان');
    }

    public function test_the_doctor_page_uses_the_real_photo_and_claims_no_credentials(): void
    {
        $this->get('/en/meet-dr-moi')
            ->assertOk()
            ->assertSee('Dr. Moi')
            ->assertSee('images/site/dr-moi-1280.webp', escape: false)
            ->assertSee('Awaiting verified information')
            ->assertSee('"@type":"BreadcrumbList"', escape: false);
    }

    public function test_the_navigation_and_footer_link_every_main_page(): void
    {
        $response = $this->get('/en')->assertOk();

        foreach (['public.about', 'public.doctor', 'public.services.index', 'public.gallery', 'public.blog.index', 'public.contact', 'public.booking', 'public.legal.privacy', 'public.legal.terms'] as $route) {
            $response->assertSee('href="'.route($route, ['locale' => 'en']).'"', escape: false);
        }

        $response->assertSee('images/brand/mark-brand.webp', escape: false)
            ->assertSee('instagram.com/dr.mohammad.amara', escape: false);
    }
}
