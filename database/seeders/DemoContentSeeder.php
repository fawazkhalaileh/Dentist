<?php

namespace Database\Seeders;

use App\Actions\Content\SaveBlogCategory;
use App\Actions\Content\SaveBlogPost;
use App\Actions\Content\SaveFaq;
use App\Actions\Content\SavePage;
use App\Actions\Content\SaveService;
use App\Models\Page;
use App\Models\User;
use App\Support\ClinicDetails;
use Illuminate\Database\Seeder;

/**
 * Placeholder website content for local development. All text is clearly
 * marked as demo text: it makes no clinical claims and contains no fake reviews.
 * Uses the same actions as the CMS so the data has the same shape.
 */
class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.test')->firstOrFail();
        $dentists = array_values(User::role('dentist')->get()->map(fn (User $u) => $u->id)->all());

        $demo = 'Demo text. Replace with the clinic\'s own description.';
        $demoAr = 'نص تجريبي. يُستبدل بوصف العيادة الخاص.';

        $services = [
            ['Check-ups and cleaning', 'الفحص والتنظيف', 30],
            ['Fillings', 'الحشوات', 45],
            ['Root canal treatment', 'علاج العصب', 90],
            ['Crowns', 'التيجان', 60],
        ];

        foreach ($services as $i => [$en, $ar, $minutes]) {
            app(SaveService::class)->handle([
                'status' => 'published',
                'is_bookable' => true,
                'duration_minutes' => $minutes,
                'sort_order' => $i,
                'featured_media_id' => null,
                'dentist_ids' => $dentists,
                'translations' => [
                    'en' => ['title' => $en, 'slug' => '', 'summary' => $demo, 'procedure_information' => null, 'seo_title' => null, 'meta_description' => null],
                    'ar' => ['title' => $ar, 'slug' => '', 'summary' => $demoAr, 'procedure_information' => null, 'seo_title' => null, 'meta_description' => null],
                ],
                'sections' => [
                    'en' => [['heading' => 'What to expect', 'body' => $demo]],
                    'ar' => [['heading' => 'ماذا تتوقع', 'body' => $demoAr]],
                ],
                'faqs' => ['en' => [], 'ar' => []],
            ]);
        }

        $category = app(SaveBlogCategory::class)->handle([
            'sort_order' => 0,
            'translations' => ['en' => ['name' => 'Clinic news', 'slug' => ''], 'ar' => ['name' => 'أخبار العيادة', 'slug' => '']],
        ]);

        app(SaveBlogPost::class)->handle([
            'status' => 'published',
            'blog_category_id' => $category->id,
            'featured_media_id' => null,
            'published_at' => null,
            'translations' => [
                'en' => ['title' => 'Welcome to our new website', 'slug' => '', 'excerpt' => $demo, 'body' => $demo, 'seo_title' => null, 'meta_description' => null],
                'ar' => ['title' => 'مرحباً بكم في موقعنا الجديد', 'slug' => '', 'excerpt' => $demoAr, 'body' => $demoAr, 'seo_title' => null, 'meta_description' => null],
            ],
        ], $admin);

        app(SaveBlogPost::class)->handle([
            'status' => 'draft',
            'blog_category_id' => null,
            'featured_media_id' => null,
            'published_at' => null,
            'translations' => [
                'en' => ['title' => 'Draft article (not public)', 'slug' => '', 'excerpt' => null, 'body' => $demo, 'seo_title' => null, 'meta_description' => null],
            ],
        ], $admin);

        foreach (array_keys(Page::KEYS) as $key) {
            app(SavePage::class)->handle(Page::firstOrNew(['key' => $key]), [
                'status' => 'draft',
                'translations' => [
                    'en' => ['title' => ucfirst($key), 'body' => $demo, 'seo_title' => null, 'meta_description' => null],
                ],
            ]);
        }

        app(SaveFaq::class)->handle(['locale' => 'en', 'question' => 'How do I request an appointment?', 'answer' => $demo, 'status' => 'published', 'sort_order' => 0]);
        app(SaveFaq::class)->handle(['locale' => 'ar', 'question' => 'كيف أطلب موعداً؟', 'answer' => $demoAr, 'status' => 'published', 'sort_order' => 0]);

        ClinicDetails::put([
            'phone' => '+000 0000 0000',
            'email' => 'clinic@example.test',
            'address' => ['en' => 'Demo address, replace in Contact details', 'ar' => 'عنوان تجريبي، يُستبدل من بيانات التواصل'],
            'opening_hours' => ['en' => 'Sun–Thu 09:00–17:00 (demo)', 'ar' => 'الأحد–الخميس ٠٩:٠٠–١٧:٠٠ (تجريبي)'],
        ], $admin->id);
    }
}
