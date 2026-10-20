<?php

namespace Tests\Feature\Cms;

use App\Enums\PublicationStatus;
use App\Models\BlogCategory;
use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\GalleryItem;
use App\Models\Page;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\CreatesContent;
use Tests\TestCase;

/**
 * The smaller CMS screens: testimonials, FAQs, blog categories, fixed pages,
 * contact messages, and the edit screens of existing records.
 */
class ContentScreensTest extends TestCase
{
    use CreatesContent, RefreshDatabase;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->editor = User::factory()->receptionist()->withTwoFactor()->create();
        $this->actingAs($this->editor);
    }

    public function test_edit_screens_open_for_existing_records(): void
    {
        Storage::fake('public');

        $service = $this->makeService(['en' => 'Existing service']);
        $post = $this->makePost(['en' => 'Existing post']);
        Livewire::test('pages::clinic.content.gallery.edit')
            ->set('beforeUpload', UploadedFile::fake()->image('before.jpg', 800, 600))
            ->set('afterUpload', UploadedFile::fake()->image('after.jpg', 800, 600))
            ->set('form.translations.en.title', 'Existing result')
            ->call('save');

        $item = GalleryItem::sole();

        // Form inputs are filled from the Livewire snapshot, so check the loaded state rather than the HTML.
        $this->get(route('clinic.content.services.edit', $service))->assertOk();
        $this->get(route('clinic.content.blog.edit', $post))->assertOk();
        $this->get(route('clinic.content.gallery.edit', $item))->assertOk();

        Livewire::test('pages::clinic.content.blog.edit', ['post' => $post])->assertSet('form.translations.en.title', 'Existing post');
        Livewire::test('pages::clinic.content.gallery.edit', ['item' => $item])->assertSet('form.translations.en.title', 'Existing result');

        foreach (array_keys(Page::KEYS) as $key) {
            $this->get(route('clinic.content.pages.edit', $key))->assertOk();
        }
    }

    public function test_a_testimonial_is_published_only_with_consent_and_can_be_edited_and_deleted(): void
    {
        $component = Livewire::test('pages::clinic.content.testimonials')
            ->call('create')
            ->set('form.author_name', 'Sara K.')
            ->set('form.body', 'Synthetic testimonial text.')
            ->set('form.rating', 5)
            ->set('form.status', 'published')
            ->call('save')
            ->assertHasErrors(['form.consent_confirmed']);

        $this->assertSame(0, Testimonial::count());

        $component->set('form.consent_confirmed', true)->call('save')->assertHasNoErrors()->assertSet('editing', false);

        $testimonial = Testimonial::sole();
        $this->assertSame(PublicationStatus::Published, $testimonial->status);
        $this->assertSame($this->editor->id, $testimonial->consent_confirmed_by);
        $this->get(route('public.testimonials', ['locale' => 'en']))->assertSee('Synthetic testimonial text.');
        $this->get(route('public.testimonials', ['locale' => 'ar']))->assertDontSee('Synthetic testimonial text.');

        $component->call('edit', $testimonial->id)
            ->assertSet('form.author_name', 'Sara K.')
            ->set('form.rating', null)
            ->set('form.status', 'draft')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($testimonial->refresh()->rating);
        $this->get(route('public.testimonials', ['locale' => 'en']))->assertDontSee('Synthetic testimonial text.');

        $component->call('delete', $testimonial->id);
        $this->assertModelMissing($testimonial);
    }

    public function test_faqs_are_created_per_language_edited_and_deleted(): void
    {
        $component = Livewire::test('pages::clinic.content.faqs')
            ->call('create')
            ->call('save')
            ->assertHasErrors(['form.question', 'form.answer']);

        $component->set('form.locale', 'ar')
            ->set('form.question', 'هل تقبلون التأمين؟')
            ->set('form.answer', 'جواب تجريبي.')
            ->set('form.status', 'published')
            ->call('save')
            ->assertHasNoErrors();

        $faq = Faq::sole();
        $this->get(route('public.faq', ['locale' => 'ar']))->assertSee('هل تقبلون التأمين؟');
        $this->get(route('public.faq', ['locale' => 'en']))->assertDontSee('هل تقبلون التأمين؟');

        $component->call('edit', $faq->id)->set('form.question', 'هل تقبلون جميع شركات التأمين؟')->call('save')->assertHasNoErrors();
        $this->assertSame('هل تقبلون جميع شركات التأمين؟', $faq->refresh()->question);

        $component->call('delete', $faq->id);
        $this->assertModelMissing($faq);
    }

    public function test_blog_categories_get_slugs_and_deleting_one_keeps_its_articles(): void
    {
        Livewire::test('pages::clinic.content.blog.categories')
            ->call('create')
            ->set('form.translations.en.name', 'Oral health')
            ->set('form.translations.ar.name', 'صحة الفم')
            ->call('save')
            ->assertHasNoErrors();

        $category = BlogCategory::with('translations')->sole();
        $this->assertSame('oral-health', $category->translation('en')?->slug);
        $this->assertSame('صحة-الفم', $category->translation('ar')?->slug);

        $post = $this->makePost(['en' => 'Categorised post']);
        $post->update(['blog_category_id' => $category->id]);
        $this->get(route('public.blog.category', ['locale' => 'en', 'slug' => 'oral-health']))->assertOk()->assertSee('Categorised post');

        Livewire::test('pages::clinic.content.blog.categories')->call('delete', $category->id);

        $this->assertModelMissing($category);
        $this->assertNull($post->refresh()->blog_category_id);
        $this->get(route('public.blog.show', ['locale' => 'en', 'slug' => 'categorised-post']))->assertOk();
    }

    public function test_a_fixed_page_is_saved_with_its_meta_description(): void
    {
        Livewire::test('pages::clinic.content.pages.edit', ['key' => 'about'])
            ->set('form.translations.en.title', 'About our clinic')
            ->set('form.translations.en.body', 'Synthetic **about** text.')
            ->set('form.translations.en.meta_description', 'Synthetic description for search engines.')
            ->set('form.status', 'published')
            ->call('save')
            ->assertHasNoErrors();

        $this->get(route('public.about', ['locale' => 'en']))
            ->assertOk()
            ->assertSee('<strong>about</strong>', escape: false)
            ->assertSee('<meta name="description" content="Synthetic description for search engines."', escape: false);

        // Saving again updates the same page instead of creating a second one.
        Livewire::test('pages::clinic.content.pages.edit', ['key' => 'about'])
            ->assertSet('form.translations.en.title', 'About our clinic')
            ->set('form.status', 'draft')
            ->call('save');

        $this->assertSame(1, Page::where('key', 'about')->count());
        $this->get(route('public.about', ['locale' => 'en']))->assertDontSee('Synthetic');
    }

    public function test_contact_messages_can_be_marked_as_handled(): void
    {
        $message = ContactMessage::create(['name' => 'Visitor', 'email' => 'v@example.test', 'message' => 'Synthetic question', 'locale' => 'en', 'ip_address' => '127.0.0.1']);

        $component = Livewire::test('pages::clinic.content.messages')
            ->assertSee('Synthetic question')
            ->call('markHandled', $message->id)
            ->assertDontSee('Synthetic question');

        $message->refresh();
        $this->assertNotNull($message->handled_at);
        $this->assertSame($this->editor->id, $message->handled_by);

        $component->set('showHandled', true)->assertSee('Synthetic question');
    }

    public function test_the_clinic_name_is_edited_per_language_with_fallbacks(): void
    {
        $this->get('/en')->assertSee('<title>Dental clinic in Amman | '.config('clinic.name').'</title>', escape: false);

        Livewire::test('pages::clinic.content.clinic-details')
            ->set('form.name.en', 'Smile Dental')
            ->call('save')
            ->assertHasNoErrors();

        // Arabic falls back to the default-language name until it is filled in.
        $this->get('/ar')->assertSee('| Smile Dental</title>', escape: false);

        Livewire::test('pages::clinic.content.clinic-details')
            ->set('form.name.ar', 'عيادة الابتسامة')
            ->call('save');

        $this->get('/en')->assertSee('<title>Dental clinic in Amman | Smile Dental</title>', escape: false)->assertSee('"name":"Smile Dental"', escape: false);
        $this->get('/ar')->assertSee('| عيادة الابتسامة</title>', escape: false);
        $this->get('/en/services')->assertSee('Treatments | Smile Dental');
    }
}
