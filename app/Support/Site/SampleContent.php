<?php

namespace App\Support\Site;

/**
 * Sample website content, shown only while the clinic has not published its own
 * records of that kind. Everything here is general, non-factual copy and is
 * always labelled as a sample on the page. Replace it by publishing real content
 * in the CMS; nothing here is ever written to the database.
 */
final class SampleContent
{
    /**
     * Treatment categories (general information, no clinic-specific claims).
     *
     * @return list<array{key: string, icon: string, en: array{title: string, summary: string}, ar: array{title: string, summary: string}}>
     */
    public static function treatments(): array
    {
        return [
            ['key' => 'general', 'icon' => 'tooth',
                'en' => ['title' => 'General Dentistry', 'summary' => 'Check-ups, cleaning and fillings that keep your teeth healthy and catch problems early.'],
                'ar' => ['title' => 'طب الأسنان العام', 'summary' => 'فحوصات دورية وتنظيف وحشوات تحافظ على صحة أسنانك وتكشف المشكلات مبكراً.']],
            ['key' => 'cosmetic', 'icon' => 'sparkle',
                'en' => ['title' => 'Cosmetic Dentistry', 'summary' => 'Thoughtful changes to shape, shade and balance, planned around your face and your smile.'],
                'ar' => ['title' => 'تجميل الأسنان', 'summary' => 'تحسينات مدروسة للشكل واللون والتناسق، مخططة لتناسب وجهك وابتسامتك.']],
            ['key' => 'whitening', 'icon' => 'whitening',
                'en' => ['title' => 'Teeth Whitening', 'summary' => 'Professional whitening to lift stains and brighten your natural shade safely.'],
                'ar' => ['title' => 'تبييض الأسنان', 'summary' => 'تبييض احترافي يزيل التصبغات ويفتّح لون أسنانك الطبيعي بأمان.']],
            ['key' => 'veneers', 'icon' => 'veneers',
                'en' => ['title' => 'Veneers', 'summary' => 'Thin, custom-made facings that can refine the colour, shape and alignment of front teeth.'],
                'ar' => ['title' => 'القشور التجميلية', 'summary' => 'قشور رقيقة مصممة خصيصاً لتحسين لون الأسنان الأمامية وشكلها واصطفافها.']],
            ['key' => 'implants', 'icon' => 'implant',
                'en' => ['title' => 'Dental Implants', 'summary' => 'A long-term way to replace missing teeth, from a single tooth to a full arch.'],
                'ar' => ['title' => 'زراعة الأسنان', 'summary' => 'حل طويل الأمد لتعويض الأسنان المفقودة، من سن واحد إلى فك كامل.']],
            ['key' => 'crowns', 'icon' => 'crown',
                'en' => ['title' => 'Crowns & Bridges', 'summary' => 'Restorations that protect weakened teeth and fill gaps with a natural-looking finish.'],
                'ar' => ['title' => 'التيجان والجسور', 'summary' => 'ترميمات تحمي الأسنان الضعيفة وتملأ الفراغات بمظهر طبيعي.']],
            ['key' => 'alignment', 'icon' => 'aligners',
                'en' => ['title' => 'Teeth Alignment', 'summary' => 'Braces or clear aligners to straighten teeth and improve your bite.'],
                'ar' => ['title' => 'تقويم الأسنان', 'summary' => 'تقويم ثابت أو شفاف لتعديل اصطفاف الأسنان وتحسين الإطباق.']],
            ['key' => 'preventive', 'icon' => 'shield',
                'en' => ['title' => 'Preventive Care', 'summary' => 'Hygiene visits, fluoride and advice that help you avoid treatment in the first place.'],
                'ar' => ['title' => 'الرعاية الوقائية', 'summary' => 'جلسات تنظيف وفلورايد ونصائح تساعدك على تجنّب العلاج من الأساس.']],
        ];
    }

    /**
     * Placeholder testimonials. Never real patients; always labelled as samples.
     *
     * @return list<array{en: array{author: string, body: string}, ar: array{author: string, body: string}}>
     */
    public static function testimonials(): array
    {
        return [
            ['en' => ['author' => 'Sample patient', 'body' => 'This is a placeholder testimonial. Real patient reviews will appear here once they are collected with permission.'],
                'ar' => ['author' => 'مراجع (نموذج)', 'body' => 'هذا رأي تجريبي للعرض فقط. ستظهر آراء المراجعين الحقيقية هنا بعد جمعها بموافقتهم.']],
            ['en' => ['author' => 'Sample patient', 'body' => 'Placeholder text describing a calm, well-explained visit. Replace it with a genuine review from the clinic.'],
                'ar' => ['author' => 'مراجع (نموذج)', 'body' => 'نص تجريبي يصف زيارة هادئة وواضحة الشرح. يُستبدل برأي حقيقي من العيادة.']],
            ['en' => ['author' => 'Sample patient', 'body' => 'Example review layout only. The clinic will publish real feedback from patients who agree to share it.'],
                'ar' => ['author' => 'مراجع (نموذج)', 'body' => 'مثال على شكل الآراء فقط. ستنشر العيادة آراء حقيقية من المراجعين الموافقين على مشاركتها.']],
        ];
    }

    /**
     * Sample articles. General dental education; the dates are demo dates and are labelled as such.
     *
     * @return list<array{key: string, category: string, image: string, minutes: int, date: string, en: array{title: string, excerpt: string, category: string}, ar: array{title: string, excerpt: string, category: string}}>
     */
    public static function posts(): array
    {
        return [
            ['key' => 'how-often-check-up', 'category' => 'prevention', 'image' => 'treatment-room', 'minutes' => 4, 'date' => '2026-10-06',
                'en' => ['title' => 'How often should you have a dental check-up?', 'excerpt' => 'Why regular visits matter, what happens at a check-up and how your dentist decides the right interval for you.', 'category' => 'Prevention'],
                'ar' => ['title' => 'كم مرة يجب أن تزور طبيب الأسنان للفحص؟', 'excerpt' => 'لماذا تهم الزيارات المنتظمة، وماذا يحدث أثناء الفحص، وكيف يحدد الطبيب الموعد المناسب لك.', 'category' => 'الوقاية']],
            ['key' => 'whitening-what-to-know', 'category' => 'cosmetic', 'image' => 'reception', 'minutes' => 5, 'date' => '2026-09-29',
                'en' => ['title' => 'Teeth whitening: what to know before you start', 'excerpt' => 'Who whitening suits, how professional whitening differs from shop products and how to keep results longer.', 'category' => 'Cosmetic'],
                'ar' => ['title' => 'تبييض الأسنان: ما يجب معرفته قبل البدء', 'excerpt' => 'لمن يناسب التبييض، وكيف يختلف التبييض الاحترافي عن المنتجات التجارية، وكيف تحافظ على النتيجة.', 'category' => 'التجميل']],
            ['key' => 'veneers-or-crowns', 'category' => 'cosmetic', 'image' => 'dr-moi', 'minutes' => 6, 'date' => '2026-09-22',
                'en' => ['title' => 'Veneers or crowns: what is the difference?', 'excerpt' => 'Both can transform a smile, but they solve different problems. A plain-language guide to choosing.', 'category' => 'Cosmetic'],
                'ar' => ['title' => 'القشور أم التيجان: ما الفرق؟', 'excerpt' => 'كلاهما يغيّر الابتسامة، لكن لكلٍّ منهما استخدام مختلف. دليل مبسّط للاختيار.', 'category' => 'التجميل']],
            ['key' => 'implants-explained', 'category' => 'restorative', 'image' => 'treatment-room', 'minutes' => 6, 'date' => '2026-09-15',
                'en' => ['title' => 'Dental implants explained, step by step', 'excerpt' => 'From the first consultation to the final tooth: what an implant is and what the treatment usually involves.', 'category' => 'Restorative'],
                'ar' => ['title' => 'زراعة الأسنان خطوة بخطوة', 'excerpt' => 'من الاستشارة الأولى حتى السن النهائي: ما هي الزرعة وماذا يتضمن العلاج عادةً.', 'category' => 'الترميم']],
            ['key' => 'sensitive-teeth', 'category' => 'prevention', 'image' => 'reception', 'minutes' => 3, 'date' => '2026-09-08',
                'en' => ['title' => 'Sensitive teeth: common causes and simple fixes', 'excerpt' => 'A sharp twinge with cold drinks is common. Here is why it happens and when to see your dentist.', 'category' => 'Prevention'],
                'ar' => ['title' => 'حساسية الأسنان: الأسباب الشائعة والحلول البسيطة', 'excerpt' => 'الألم الخفيف مع المشروبات الباردة شائع. إليك أسبابه ومتى يجب زيارة الطبيب.', 'category' => 'الوقاية']],
            ['key' => 'first-visit-nerves', 'category' => 'patient-guide', 'image' => 'dr-moi', 'minutes' => 4, 'date' => '2026-09-01',
                'en' => ['title' => 'Nervous about the dentist? How we make visits easier', 'excerpt' => 'Practical ways to feel calmer in the chair, and what to tell your dentist so they can help.', 'category' => 'Patient guide'],
                'ar' => ['title' => 'تشعر بالقلق من زيارة طبيب الأسنان؟ هكذا نجعلها أسهل', 'excerpt' => 'طرق عملية لتشعر بالهدوء على كرسي العلاج، وما الذي تخبر به طبيبك ليساعدك.', 'category' => 'دليل المراجع']],
        ];
    }
}
