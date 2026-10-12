<?php

namespace Tests\Feature\Public;

use App\Actions\Content\SavePage;
use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\Page;
use App\Models\StaffProfile;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesContent;
use Tests\TestCase;

class PublicContentTest extends TestCase
{
    use CreatesContent, RefreshDatabase;

    public function test_published_service_is_shown_with_its_localised_slug_and_alternates(): void
    {
        $this->makeService(['en' => 'Teeth whitening', 'ar' => 'تبييض الأسنان']);

        $this->get('/en/services/teeth-whitening')
            ->assertOk()
            ->assertSee('Teeth whitening')
            ->assertSee('hreflang="ar" href="'.route('public.services.show', ['locale' => 'ar', 'slug' => 'تبييض-الأسنان']).'"', escape: false);

        $this->get(route('public.services.show', ['locale' => 'ar', 'slug' => 'تبييض-الأسنان']))
            ->assertOk()
            ->assertSee('تبييض الأسنان');

        $this->get(route('public.services.index', ['locale' => 'en']))->assertSee('Teeth whitening');
    }

    public function test_draft_services_are_not_public(): void
    {
        $this->makeService(['en' => 'Secret service'], status: 'draft');

        $this->get('/en/services/secret-service')->assertNotFound();
        $this->get(route('public.services.index', ['locale' => 'en']))->assertDontSee('Secret service');
    }

    public function test_a_service_without_a_translation_is_hidden_in_that_language(): void
    {
        $this->makeService(['en' => 'English only']);

        $this->get(route('public.services.index', ['locale' => 'ar']))->assertDontSee('English only');
        $this->get('/ar/services/english-only')->assertNotFound();
    }

    public function test_blog_shows_only_published_and_not_scheduled_posts(): void
    {
        $this->makePost(['en' => 'Visible post']);
        $this->makePost(['en' => 'Draft post'], status: 'draft');
        $this->makePost(['en' => 'Future post'], publishedAt: now()->addWeek()->toDateTimeString());

        $this->get(route('public.blog.index', ['locale' => 'en']))
            ->assertSee('Visible post')
            ->assertDontSee('Draft post')
            ->assertDontSee('Future post');

        $this->get('/en/blog/visible-post')->assertOk()->assertSee('"@type":"BlogPosting"', escape: false);
        $this->get('/en/blog/draft-post')->assertNotFound();
        $this->get('/en/blog/future-post')->assertNotFound();
    }

    public function test_markdown_content_cannot_inject_html_or_scripts(): void
    {
        $this->makePost(['en' => 'Unsafe']);
        DB::table('blog_post_translations')->update([
            'body' => "Safe **bold**\n\n<script>alert('x')</script>\n\n[link](javascript:alert(1))",
        ]);

        $this->get('/en/blog/unsafe')
            ->assertOk()
            ->assertSee('<strong>bold</strong>', escape: false)
            ->assertDontSee("<script>alert('x')</script>", escape: false)
            ->assertDontSee('javascript:alert', escape: false);
    }

    public function test_only_public_active_dentists_are_listed(): void
    {
        StaffProfile::factory()->withEnglish()->create(['slug' => 'shown']);
        StaffProfile::factory()->withEnglish()->create(['slug' => 'hidden', 'is_public' => false]);
        $inactive = User::factory()->dentist()->inactive()->create();
        StaffProfile::factory()->withEnglish()->create(['slug' => 'inactive', 'user_id' => $inactive->id]);

        $this->get(route('public.dentists.index', ['locale' => 'en']))
            ->assertSee('/en/dentists/shown', escape: false)
            ->assertDontSee('/en/dentists/hidden', escape: false)
            ->assertDontSee('/en/dentists/inactive', escape: false);

        $this->get('/en/dentists/hidden')->assertNotFound();
    }

    public function test_pages_show_published_content_or_an_explanation(): void
    {
        $this->get(route('public.about', ['locale' => 'en']))->assertOk()->assertSee('This page is being prepared');

        app(SavePage::class)->handle(Page::firstOrNew(['key' => 'about']), [
            'status' => 'published',
            'translations' => ['en' => ['title' => 'Our story', 'body' => 'We opened in a synthetic year.', 'seo_title' => null, 'meta_description' => null]],
        ]);

        $this->get(route('public.about', ['locale' => 'en']))->assertSee('Our story')->assertSee('synthetic year');
    }

    public function test_testimonials_and_faqs_are_filtered_by_locale_and_status(): void
    {
        Testimonial::create(['locale' => 'en', 'author_name' => 'A. B.', 'body' => 'Published one', 'status' => 'published', 'consent_confirmed_at' => now()]);
        Testimonial::create(['locale' => 'en', 'author_name' => 'C. D.', 'body' => 'Draft one', 'status' => 'draft']);
        Faq::create(['locale' => 'ar', 'question' => 'سؤال منشور', 'answer' => 'جواب', 'status' => 'published']);

        $this->get(route('public.testimonials', ['locale' => 'en']))->assertSee('Published one')->assertDontSee('Draft one');
        $this->get(route('public.faq', ['locale' => 'ar']))->assertSee('سؤال منشور')->assertSee('"@type":"FAQPage"', escape: false);
        $this->get(route('public.faq', ['locale' => 'en']))->assertDontSee('سؤال منشور');
    }

    public function test_contact_form_stores_an_encrypted_message(): void
    {
        $this->post(route('public.contact.store', ['locale' => 'en']), [
            'name' => 'Synthetic Visitor',
            'email' => 'visitor@example.test',
            'message' => 'I have a synthetic toothache.',
        ])->assertRedirect(route('public.contact', ['locale' => 'en']))->assertSessionHas('status', 'contact-sent');

        $message = ContactMessage::sole();
        $this->assertSame('I have a synthetic toothache.', $message->message);
        $this->assertNotSame('I have a synthetic toothache.', DB::table('contact_messages')->value('message'));
        $this->assertSame('en', $message->locale);
    }

    public function test_contact_form_validates_and_rejects_bots(): void
    {
        $this->post(route('public.contact.store', ['locale' => 'en']), ['name' => 'X', 'message' => 'Hi'])
            ->assertSessionHasErrors(['email', 'phone']);

        $this->post(route('public.contact.store', ['locale' => 'en']), [
            'name' => 'Bot', 'email' => 'bot@example.test', 'message' => 'Spam', 'website' => 'http://spam.example',
        ])->assertSessionHasErrors('website');

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_contact_form_is_rate_limited(): void
    {
        foreach (range(1, 3) as $i) {
            $this->post(route('public.contact.store', ['locale' => 'en']), ['name' => 'V', 'phone' => '123', 'message' => "Message {$i}"]);
        }

        $this->post(route('public.contact.store', ['locale' => 'en']), ['name' => 'V', 'phone' => '123', 'message' => 'One too many'])
            ->assertTooManyRequests();
    }

    public function test_sitemap_lists_published_content_only(): void
    {
        $this->makeService(['en' => 'Listed service', 'ar' => 'خدمة مدرجة']);
        $this->makeService(['en' => 'Hidden service'], status: 'draft');
        $this->makePost(['ar' => 'مقال عربي']);

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee('/en/services/listed-service', escape: false)
            ->assertSee(route('public.services.show', ['locale' => 'ar', 'slug' => 'خدمة-مدرجة']), escape: false)
            ->assertSee(route('public.blog.show', ['locale' => 'ar', 'slug' => 'مقال-عربي']), escape: false)
            ->assertDontSee('hidden-service', escape: false);
    }

    public function test_listing_pages_have_a_meta_description_in_each_language(): void
    {
        foreach (['public.blog.index', 'public.testimonials', 'public.faq'] as $route) {
            foreach (['en', 'ar'] as $locale) {
                $this->get(route($route, ['locale' => $locale]))
                    ->assertOk()
                    ->assertSee('<meta name="description"', escape: false);
            }
        }

        $this->get(route('public.faq', ['locale' => 'ar']))->assertSee('إجابات عن الأسئلة الشائعة');
    }
}
