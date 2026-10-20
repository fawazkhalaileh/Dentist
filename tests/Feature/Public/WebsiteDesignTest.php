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
        $this->get('/en/meet-dr-moi')->assertRedirect('/en/meet-dr-mohammed-amara')->assertStatus(301);

        $this->get('/ar/meet-dr-mohammed-amara')->assertOk()->assertSee('د. محمد عماره');

        $this->get('/en/meet-dr-mohammed-amara')
            ->assertOk()
            ->assertSee('Dr. Mohammed Amara')
            ->assertSee('images/site/doctor-1280.webp', escape: false)
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

    public function test_sample_treatments_have_unindexed_information_pages_until_services_are_published(): void
    {
        $this->get('/en/services')->assertOk()->assertSee('/en/services/whitening', escape: false);

        $this->get('/en/services/whitening')
            ->assertOk()
            ->assertSee('Teeth Whitening')
            ->assertSee('What is involved')
            ->assertSee('Sample treatment page, to be confirmed by the clinic')
            ->assertSee('<meta name="robots" content="noindex, follow" />', escape: false);
        $this->get('/ar/services/implants')->assertOk()->assertSee('زراعة الأسنان');
        $this->get('/en/services/not-a-treatment')->assertNotFound();

        $this->makeService(['en' => 'Gum treatment']);
        $this->get('/en/services/whitening')->assertNotFound();
    }

    public function test_sample_articles_can_be_read_filtered_and_searched_until_posts_are_published(): void
    {
        $this->get('/en/blog')->assertOk()->assertSee('Sample articles with demo dates, to be replaced by the clinic');
        $this->get('/en/blog/veneers-or-crowns')
            ->assertOk()
            ->assertSee('Veneers or crowns: what is the difference?')
            ->assertSee('Sample article')
            ->assertSee('<meta name="robots" content="noindex, follow" />', escape: false);
        $this->get('/ar/blog/sensitive-teeth')->assertOk()->assertSee('حساسية الأسنان');

        $this->get('/en/blog/category/cosmetic')->assertOk()->assertSee('Teeth whitening: what to know')->assertDontSee('Dental implants explained');
        $this->get('/en/blog?q=implant')->assertOk()->assertSee('Dental implants explained')->assertDontSee('Sensitive teeth: common causes');

        $this->makePost(['en' => 'Real clinic news']);
        $this->get('/en/blog/veneers-or-crowns')->assertNotFound();
        $this->get('/en/blog')->assertSee('Real clinic news')->assertDontSee('Sample article');
    }

    public function test_published_articles_can_be_searched(): void
    {
        $this->makePost(['en' => 'Caring for braces']);
        $this->makePost(['en' => 'Healthy gums']);

        $this->get('/en/blog?q=braces')->assertOk()->assertSee('Caring for braces')->assertDontSee('Healthy gums');
        $this->get('/en/blog?q=%25')->assertOk()->assertDontSee('Caring for braces');
    }

    public function test_the_gallery_shows_placeholders_without_consented_cases(): void
    {
        $this->get('/en/gallery')
            ->assertOk()
            ->assertSee('Placeholder until consent is confirmed')
            ->assertDontSee('/storage/media/', escape: false);
    }

    public function test_the_about_contact_and_booking_pages_keep_their_forms_and_content(): void
    {
        $this->get('/en/about')->assertOk()->assertSee('Our values')->assertSee('images/site/reception-1200.webp', escape: false);
        $this->get('/en/contact')->assertOk()->assertSee('action="'.route('public.contact.store', ['locale' => 'en']).'"', escape: false)->assertSee('name="website"', escape: false);
        $this->get('/en/book')->assertOk()->assertSee('action="'.route('public.booking.store', ['locale' => 'en']).'"', escape: false)->assertSee('What happens next?');
    }
}
