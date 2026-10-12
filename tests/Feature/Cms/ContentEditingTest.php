<?php

namespace Tests\Feature\Cms;

use App\Enums\PublicationStatus;
use App\Models\Appointment;
use App\Models\BlogPost;
use App\Models\GalleryItem;
use App\Models\Media;
use App\Models\Service;
use App\Models\Testimonial;
use App\Models\User;
use App\Support\ClinicDetails;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use LogicException;
use Tests\Concerns\CreatesContent;
use Tests\TestCase;

class ContentEditingTest extends TestCase
{
    use CreatesContent, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->receptionist()->withTwoFactor()->create());
    }

    public function test_a_service_is_created_in_two_languages_with_sections_and_faqs(): void
    {
        $dentist = User::factory()->dentist()->create();

        Livewire::test('pages::clinic.content.services.edit')
            ->set('form.translations.en.title', 'Dental implants')
            ->set('form.translations.ar.title', 'زراعة الأسنان')
            ->set('form.translations.en.summary', 'Replacing missing teeth.')
            ->call('addSection', 'en')
            ->set('form.sections.en.0.heading', 'How it works')
            ->set('form.sections.en.0.body', 'Step by step.')
            ->call('addFaq', 'ar')
            ->set('form.faqs.ar.0.question', 'هل هو مؤلم؟')
            ->set('form.faqs.ar.0.answer', 'جواب تجريبي.')
            ->set('form.dentist_ids', [$dentist->id])
            ->set('form.status', 'published')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $service = Service::with(['translations', 'sections', 'faqs', 'dentists'])->sole();

        $this->assertSame(PublicationStatus::Published, $service->status);
        $this->assertNotNull($service->published_at);
        $this->assertSame('dental-implants', $service->translation('en')?->slug);
        $this->assertSame('زراعة-الأسنان', $service->translation('ar')?->slug);
        $this->assertSame(['en'], $service->sections->pluck('locale')->all());
        $this->assertSame(['ar'], $service->faqs->pluck('locale')->all());
        $this->assertSame([$dentist->id], $service->dentists->pluck('id')->all());
    }

    public function test_the_default_language_title_is_required(): void
    {
        Livewire::test('pages::clinic.content.services.edit')
            ->set('form.translations.ar.title', 'عنوان فقط بالعربية')
            ->call('save')
            ->assertHasErrors(['form.translations.en.title']);

        $this->assertSame(0, Service::count());
    }

    public function test_slugs_must_be_unique_per_language(): void
    {
        $this->makeService(['en' => 'Crowns']);

        Livewire::test('pages::clinic.content.services.edit')
            ->set('form.translations.en.title', 'Crowns')
            ->call('save')
            ->assertHasErrors(['form.translations.en.slug']);
    }

    public function test_editing_keeps_the_slug_and_can_remove_a_translation(): void
    {
        $service = $this->makeService(['en' => 'Braces', 'ar' => 'تقويم']);

        Livewire::test('pages::clinic.content.services.edit', ['service' => $service])
            ->assertSet('form.translations.en.slug', 'braces')
            ->set('form.translations.en.title', 'Braces and aligners')
            ->set('form.translations.ar.title', '')
            ->call('save')
            ->assertHasNoErrors();

        $service->refresh()->load('translations');
        $this->assertSame('braces', $service->translation('en')?->slug, 'Changing the title must not break existing URLs.');
        $this->assertNull($service->translation('ar'));
    }

    public function test_services_with_appointments_cannot_be_deleted(): void
    {
        $service = $this->makeService(['en' => 'Booked service']);
        Appointment::factory()->create(['service_id' => $service->id]);

        Livewire::test('pages::clinic.content.services.index')->call('delete', $service->id);

        $this->assertModelExists($service);
    }

    public function test_a_blog_post_can_be_written_in_arabic_only_and_records_its_author(): void
    {
        Livewire::test('pages::clinic.content.blog.edit')
            ->set('form.translations.ar.title', 'نصائح للعناية بالأسنان')
            ->set('form.translations.ar.body', 'نص تجريبي')
            ->set('form.status', 'published')
            ->call('save')
            ->assertHasNoErrors();

        $post = BlogPost::with('translations')->sole();
        $this->assertSame(['ar'], $post->translatedLocales());
        $this->assertSame(auth()->id(), $post->author_id);
    }

    public function test_a_blog_post_needs_at_least_one_language(): void
    {
        Livewire::test('pages::clinic.content.blog.edit')
            ->call('save')
            ->assertHasErrors(['form.translations.en.title', 'form.translations.ar.title']);
    }

    public function test_gallery_items_cannot_be_published_without_consent(): void
    {
        Storage::fake('public');

        Livewire::test('pages::clinic.content.gallery.edit')
            ->set('beforeUpload', UploadedFile::fake()->image('before.jpg', 800, 600))
            ->set('afterUpload', UploadedFile::fake()->image('after.jpg', 800, 600))
            ->set('form.translations.en.title', 'Whitening result')
            ->set('form.status', 'published')
            ->call('save')
            ->assertHasErrors(['form.consent_confirmed']);

        $this->assertSame(0, GalleryItem::count());
    }

    public function test_consent_is_recorded_with_who_and_when(): void
    {
        Storage::fake('public');

        Livewire::test('pages::clinic.content.gallery.edit')
            ->set('beforeUpload', UploadedFile::fake()->image('before.jpg', 800, 600))
            ->set('afterUpload', UploadedFile::fake()->image('after.jpg', 800, 600))
            ->set('form.translations.en.title', 'Whitening result')
            ->set('form.consent_confirmed', true)
            ->set('form.status', 'published')
            ->call('save')
            ->assertHasNoErrors();

        $item = GalleryItem::sole();
        $this->assertNotNull($item->consent_confirmed_at);
        $this->assertSame(auth()->id(), $item->consent_confirmed_by);
        $this->get(route('public.gallery', ['locale' => 'en']))->assertSee('Whitening result');
    }

    public function test_the_model_refuses_publication_without_consent_from_any_code_path(): void
    {
        $this->expectException(LogicException::class);

        Testimonial::create(['locale' => 'en', 'author_name' => 'X', 'body' => 'Y', 'status' => 'published']);
    }

    public function test_uploaded_images_are_converted_to_webp_in_two_sizes(): void
    {
        Storage::fake('public');

        Livewire::test('pages::clinic.content.media')
            ->set('upload', UploadedFile::fake()->image('photo.jpg', 2400, 1200))
            ->assertHasNoErrors();

        $media = Media::sole();
        $this->assertSame('image/webp', $media->mime_type);
        $this->assertSame(Media::FULL_WIDTH, $media->width);
        $this->assertSame(800, $media->height);
        Storage::disk('public')->assertExists([$media->path, $media->thumb_path]);
        $this->assertStringEndsWith('.webp', $media->path);
    }

    public function test_non_image_uploads_are_rejected(): void
    {
        Storage::fake('public');

        Livewire::test('pages::clinic.content.media')
            ->set('upload', UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'))
            ->assertHasErrors(['upload']);

        $this->assertSame(0, Media::count());
    }

    public function test_images_in_use_cannot_be_deleted(): void
    {
        Storage::fake('public');
        Livewire::test('pages::clinic.content.media')->set('upload', UploadedFile::fake()->image('photo.jpg', 800, 600));
        $media = Media::sole();
        $this->makeService(['en' => 'With image'], overrides: ['featured_media_id' => $media->id]);

        Livewire::test('pages::clinic.content.media')->call('delete', $media->id);
        $this->assertModelExists($media);

        Service::query()->update(['featured_media_id' => null]);
        Livewire::test('pages::clinic.content.media')->call('delete', $media->id);
        $this->assertModelMissing($media);
        Storage::disk('public')->assertMissing($media->path);
    }

    public function test_clinic_details_are_saved_and_shown_publicly(): void
    {
        Livewire::test('pages::clinic.content.clinic-details')
            ->set('form.phone', '+962 6 000 0000')
            ->set('form.address.en', 'Synthetic Street 1')
            ->set('form.address.ar', 'شارع تجريبي 1')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Synthetic Street 1', ClinicDetails::get('address', 'en'));
        $this->get(route('public.contact', ['locale' => 'ar']))->assertSee('شارع تجريبي 1')->assertSee('+962 6 000 0000');
    }
}
