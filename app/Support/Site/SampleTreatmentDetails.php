<?php

namespace App\Support\Site;

/**
 * General, non-clinic-specific information for the sample treatment pages.
 * Wording stays cautious ("usually", "your dentist will advise") because it is
 * shown before the clinic has written its own. Replaced by publishing services.
 */
final class SampleTreatmentDetails
{
    /**
     * @return array<string, array{en: array{intro: string, suitable: list<string>, steps: list<string>, faqs: list<array{0: string, 1: string}>}, ar: array{intro: string, suitable: list<string>, steps: list<string>, faqs: list<array{0: string, 1: string}>}}>
     */
    public static function all(): array
    {
        return [
            'general' => [
                'en' => [
                    'intro' => 'General dentistry covers the everyday care that keeps your mouth healthy: examinations, professional cleaning, fillings and advice. Regular visits help find small problems before they become painful or costly.',
                    'suitable' => ['Anyone due a routine check-up', 'Patients with a toothache, sensitivity or a broken filling', 'Families looking for ongoing care'],
                    'steps' => ['A conversation about your health and any concerns', 'A careful examination of teeth, gums and soft tissues', 'X-rays only when they are needed', 'A clear explanation of what we found and your options'],
                    'faqs' => [['How long does a check-up take?', 'Usually around 30 minutes, a little longer if cleaning is done at the same visit.'], ['Do I need X-rays every time?', 'No. Your dentist will recommend X-rays only when they help with a diagnosis.']],
                ],
                'ar' => [
                    'intro' => 'يشمل طب الأسنان العام الرعاية اليومية التي تحافظ على صحة فمك: الفحص والتنظيف الاحترافي والحشوات والنصائح. وتساعد الزيارات المنتظمة على اكتشاف المشكلات الصغيرة قبل أن تصبح مؤلمة أو مكلفة.',
                    'suitable' => ['كل من حان موعد فحصه الدوري', 'من يعاني من ألم أو حساسية أو حشوة مكسورة', 'العائلات التي تبحث عن رعاية مستمرة'],
                    'steps' => ['حديث عن صحتك وأي مخاوف لديك', 'فحص دقيق للأسنان واللثة والأنسجة', 'صور أشعة عند الحاجة فقط', 'شرح واضح لما وجدناه وللخيارات المتاحة'],
                    'faqs' => [['كم يستغرق الفحص؟', 'نحو 30 دقيقة عادةً، وأكثر قليلاً إذا تم التنظيف في الزيارة نفسها.'], ['هل أحتاج إلى أشعة في كل زيارة؟', 'لا. يوصي الطبيب بالأشعة فقط عندما تساعد في التشخيص.']],
                ],
            ],
            'cosmetic' => [
                'en' => [
                    'intro' => 'Cosmetic dentistry improves the appearance of your smile, from subtle changes in shade to reshaping and replacing teeth. Good cosmetic work starts with healthy teeth and gums, and with a plan that suits your face.',
                    'suitable' => ['People unhappy with the colour, shape or spacing of their teeth', 'Patients with chipped or worn front teeth', 'Anyone preparing for a special occasion who wants advice in good time'],
                    'steps' => ['A consultation to understand what you would like to change', 'An examination to make sure teeth and gums are healthy first', 'A discussion of options, timescales and costs', 'Treatment in stages, with a chance to review along the way'],
                    'faqs' => [['Will the result look natural?', 'That is the aim. Your dentist will plan shape and shade around your features and show you what to expect.'], ['Is cosmetic treatment permanent?', 'It depends on the treatment. Whitening fades over time; veneers and crowns last many years with good care.']],
                ],
                'ar' => [
                    'intro' => 'يحسّن تجميل الأسنان مظهر ابتسامتك، من تغييرات بسيطة في اللون إلى إعادة تشكيل الأسنان أو تعويضها. ويبدأ العمل التجميلي الجيد بأسنان ولثة سليمة، وبخطة تناسب ملامح وجهك.',
                    'suitable' => ['من لا يرضيه لون أسنانه أو شكلها أو المسافات بينها', 'من لديه أسنان أمامية مكسورة أو متآكلة', 'من يستعد لمناسبة خاصة ويريد استشارة مبكرة'],
                    'steps' => ['استشارة لفهم ما تريد تغييره', 'فحص للتأكد من سلامة الأسنان واللثة أولاً', 'مناقشة الخيارات والمدة والتكلفة', 'علاج على مراحل مع فرصة للمراجعة خلالها'],
                    'faqs' => [['هل ستبدو النتيجة طبيعية؟', 'هذا هو الهدف. يخطط الطبيب الشكل واللون بما يناسب ملامحك ويوضح لك النتيجة المتوقعة.'], ['هل العلاج التجميلي دائم؟', 'يعتمد على نوع العلاج. يخفّ التبييض مع الوقت، بينما تدوم القشور والتيجان سنوات طويلة مع العناية الجيدة.']],
                ],
            ],
            'whitening' => [
                'en' => [
                    'intro' => 'Professional whitening lightens the natural colour of your teeth using carefully controlled products. It works on natural teeth only, so existing fillings, crowns or veneers will not change colour.',
                    'suitable' => ['Adults with healthy teeth and gums', 'Stains from coffee, tea or smoking', 'Teeth that have darkened gradually with age'],
                    'steps' => ['A check-up to make sure whitening is suitable', 'Recording your starting shade', 'Whitening in the clinic, at home with custom trays, or both', 'A review of the result and advice on keeping it'],
                    'faqs' => [['Does whitening damage teeth?', 'When it is supervised by a dentist and used as directed, whitening is considered safe. Some people notice short-lived sensitivity.'], ['How long do results last?', 'Often a year or more, depending on diet and habits. Occasional top-ups can help.']],
                ],
                'ar' => [
                    'intro' => 'يفتّح التبييض الاحترافي اللون الطبيعي لأسنانك باستخدام مواد مضبوطة بعناية. ويعمل على الأسنان الطبيعية فقط، لذلك لا يتغير لون الحشوات أو التيجان أو القشور الموجودة.',
                    'suitable' => ['البالغون ذوو الأسنان واللثة السليمة', 'التصبغات الناتجة عن القهوة أو الشاي أو التدخين', 'الأسنان التي اسودّت تدريجياً مع التقدم في العمر'],
                    'steps' => ['فحص للتأكد من ملاءمة التبييض لك', 'تسجيل لون أسنانك قبل البدء', 'التبييض في العيادة أو في المنزل بقوالب خاصة أو كليهما', 'مراجعة النتيجة ونصائح للحفاظ عليها'],
                    'faqs' => [['هل يضر التبييض بالأسنان؟', 'عندما يتم بإشراف طبيب الأسنان وحسب التعليمات يُعدّ آمناً. وقد يشعر البعض بحساسية مؤقتة.'], ['كم تدوم النتيجة؟', 'غالباً سنة أو أكثر حسب النظام الغذائي والعادات، وقد تساعد جلسات التجديد.']],
                ],
            ],
            'veneers' => [
                'en' => [
                    'intro' => 'Veneers are thin, custom-made facings bonded to the front of teeth. They can change colour, shape and the appearance of small gaps or uneven edges, and are designed to blend with your natural smile.',
                    'suitable' => ['Front teeth that are chipped, worn or discoloured', 'Small gaps or slightly uneven teeth', 'Patients with healthy gums and a stable bite'],
                    'steps' => ['A design consultation, often with photographs', 'Gentle preparation of the tooth surface where needed', 'A trial or temporary stage to preview the shape', 'Fitting and polishing the final veneers'],
                    'faqs' => [['Do veneers need much tooth removed?', 'Usually very little, and sometimes none. Your dentist will explain what is needed for your teeth.'], ['How do I care for veneers?', 'Brush and floss as normal, keep regular check-ups and avoid biting very hard objects.']],
                ],
                'ar' => [
                    'intro' => 'القشور التجميلية طبقات رقيقة مصنوعة خصيصاً تُثبّت على الواجهة الأمامية للأسنان. يمكنها تغيير اللون والشكل وإخفاء الفراغات الصغيرة أو الحواف غير المتساوية، وتُصمم لتنسجم مع ابتسامتك الطبيعية.',
                    'suitable' => ['الأسنان الأمامية المكسورة أو المتآكلة أو المتصبغة', 'الفراغات الصغيرة أو عدم الانتظام البسيط', 'من لديه لثة سليمة وإطباق مستقر'],
                    'steps' => ['استشارة تصميم، غالباً مع صور فوتوغرافية', 'تحضير لطيف لسطح السن عند الحاجة', 'مرحلة تجريبية أو مؤقتة لمعاينة الشكل', 'تركيب القشور النهائية وتلميعها'],
                    'faqs' => [['هل تتطلب القشور إزالة جزء كبير من السن؟', 'عادةً جزء صغير جداً، وأحياناً لا شيء. سيشرح لك الطبيب ما تحتاجه أسنانك.'], ['كيف أعتني بالقشور؟', 'بالتنظيف بالفرشاة والخيط كالمعتاد، والفحص الدوري، وتجنب عضّ الأشياء الصلبة جداً.']],
                ],
            ],
            'implants' => [
                'en' => [
                    'intro' => 'A dental implant is a small titanium or ceramic post placed in the jaw to support a crown, bridge or denture. Implants can replace a single tooth or several, and are cared for much like natural teeth.',
                    'suitable' => ['Adults missing one or more teeth', 'People with loose or uncomfortable dentures', 'Patients with enough healthy bone, or who can have it rebuilt'],
                    'steps' => ['A consultation and 3D assessment of the bone', 'Placing the implant, usually under local anaesthetic', 'A healing period while the implant bonds with the bone', 'Fitting the final crown, bridge or denture'],
                    'faqs' => [['Is implant treatment painful?', 'Most patients are surprised by how comfortable it is. Some soreness for a few days afterwards is normal.'], ['How long does the whole process take?', 'Often a few months from start to finish, depending on healing and whether bone grafting is needed.']],
                ],
                'ar' => [
                    'intro' => 'زرعة الأسنان دعامة صغيرة من التيتانيوم أو السيراميك توضع في عظم الفك لتثبيت تاج أو جسر أو طقم. يمكن أن تعوّض سناً واحداً أو عدة أسنان، وتتم العناية بها كالأسنان الطبيعية تقريباً.',
                    'suitable' => ['البالغون الذين فقدوا سناً أو أكثر', 'من يعاني من طقم متحرك أو غير مريح', 'من لديه عظم كافٍ، أو يمكن تعويضه'],
                    'steps' => ['استشارة وتقييم ثلاثي الأبعاد للعظم', 'وضع الزرعة، عادةً بتخدير موضعي', 'فترة التئام تلتحم فيها الزرعة بالعظم', 'تركيب التاج أو الجسر أو الطقم النهائي'],
                    'faqs' => [['هل زراعة الأسنان مؤلمة؟', 'يتفاجأ معظم المراجعين بمدى الراحة أثناءها. ومن الطبيعي الشعور ببعض الألم لأيام قليلة بعدها.'], ['كم تستغرق المراحل كاملة؟', 'غالباً بضعة أشهر من البداية حتى النهاية، حسب الالتئام والحاجة إلى تطعيم العظم.']],
                ],
            ],
            'crowns' => [
                'en' => [
                    'intro' => 'A crown covers and protects a tooth that is weakened, heavily filled or root-treated. A bridge uses neighbouring teeth or implants to replace a missing tooth. Both are made to match the colour and shape of your own teeth.',
                    'suitable' => ['Cracked or heavily filled teeth', 'Teeth after root canal treatment', 'A gap left by a missing tooth'],
                    'steps' => ['An examination and, where needed, X-rays', 'Shaping the tooth and taking a precise scan or impression', 'A temporary crown while the final one is made', 'Fitting, adjusting the bite and polishing'],
                    'faqs' => [['What are crowns made of?', 'Usually tooth-coloured ceramics such as zirconia or lithium disilicate. Your dentist will recommend the best option for the tooth.'], ['How long do crowns last?', 'Many last ten years or more with good home care and regular check-ups.']],
                ],
                'ar' => [
                    'intro' => 'يغطي التاج السن الضعيف أو كثير الحشوات أو المعالج عصبياً ويحميه. أما الجسر فيعتمد على الأسنان المجاورة أو الزرعات لتعويض سن مفقود. ويُصنع كلاهما ليطابق لون أسنانك وشكلها.',
                    'suitable' => ['الأسنان المتشققة أو كثيرة الحشوات', 'الأسنان بعد علاج العصب', 'الفراغ الناتج عن سن مفقود'],
                    'steps' => ['فحص وصور أشعة عند الحاجة', 'تشكيل السن وأخذ مسح أو طبعة دقيقة', 'تاج مؤقت ريثما يُصنع التاج النهائي', 'التركيب وضبط الإطباق والتلميع'],
                    'faqs' => [['مما تُصنع التيجان؟', 'عادةً من خزف بلون الأسنان مثل الزيركونيا أو الإي ماكس. وسيوصي الطبيب بالأنسب لسنّك.'], ['كم تدوم التيجان؟', 'يدوم كثير منها عشر سنوات أو أكثر مع العناية المنزلية الجيدة والفحص الدوري.']],
                ],
            ],
            'alignment' => [
                'en' => [
                    'intro' => 'Orthodontic treatment gradually moves teeth into better positions using fixed braces or removable clear aligners. Straighter teeth can be easier to clean and can improve how your teeth meet.',
                    'suitable' => ['Crowded, crooked or widely spaced teeth', 'Bite problems such as overbite or crossbite', 'Teens and adults alike'],
                    'steps' => ['An assessment with photographs, scans and X-rays', 'A treatment plan showing the expected movement', 'Regular reviews while braces or aligners do their work', 'Retainers to keep teeth in their new position'],
                    'faqs' => [['Braces or clear aligners?', 'Both can work well. The right choice depends on the type of movement needed and your lifestyle.'], ['How long does treatment take?', 'It varies widely, from several months for minor changes to longer for complex cases.']],
                ],
                'ar' => [
                    'intro' => 'يحرّك تقويم الأسنان الأسنان تدريجياً إلى مواضع أفضل باستخدام تقويم ثابت أو مصففات شفافة متحركة. وتكون الأسنان المنتظمة أسهل في التنظيف وقد تحسّن طريقة التقاء الأسنان.',
                    'suitable' => ['الأسنان المزدحمة أو الملتوية أو المتباعدة', 'مشكلات الإطباق مثل البروز أو العضة المعكوسة', 'المراهقون والبالغون على حد سواء'],
                    'steps' => ['تقييم بالصور والمسح والأشعة', 'خطة علاج توضح الحركة المتوقعة', 'مراجعات منتظمة أثناء عمل التقويم أو المصففات', 'مثبتات للحفاظ على الأسنان في موضعها الجديد'],
                    'faqs' => [['تقويم ثابت أم شفاف؟', 'كلاهما فعّال. ويعتمد الاختيار على نوع الحركة المطلوبة وأسلوب حياتك.'], ['كم تستغرق مدة العلاج؟', 'تختلف كثيراً، من بضعة أشهر للتعديلات البسيطة إلى مدة أطول للحالات المعقدة.']],
                ],
            ],
            'preventive' => [
                'en' => [
                    'intro' => 'Preventive care aims to stop problems before they start. Professional cleaning, fluoride, fissure sealants and simple advice on brushing and diet all help protect your teeth for the long term.',
                    'suitable' => ['Children and adults of every age', 'People prone to gum bleeding or tartar build-up', 'Anyone who wants to avoid future treatment'],
                    'steps' => ['A check of your gums and risk of decay', 'Scaling and polishing to remove tartar and stains', 'Fluoride or sealants where they help', 'Personal tips for brushing, cleaning between teeth and diet'],
                    'faqs' => [['How often should I have a hygiene visit?', 'Many people benefit from every six months, but your dentist may suggest a different interval.'], ['Are sealants only for children?', 'They are most common for children’s back teeth, but can help some adults too.']],
                ],
                'ar' => [
                    'intro' => 'تهدف الرعاية الوقائية إلى منع المشكلات قبل حدوثها. فالتنظيف الاحترافي والفلورايد وسدّ الشقوق والنصائح البسيطة حول التنظيف والغذاء تحمي أسنانك على المدى الطويل.',
                    'suitable' => ['الأطفال والبالغون من جميع الأعمار', 'من يعاني من نزف اللثة أو تراكم الجير', 'كل من يريد تجنّب العلاج مستقبلاً'],
                    'steps' => ['فحص اللثة وتقدير خطر التسوس', 'إزالة الجير والتصبغات والتلميع', 'الفلورايد أو سدّ الشقوق عند الحاجة', 'نصائح شخصية للتنظيف بالفرشاة وبين الأسنان والغذاء'],
                    'faqs' => [['كم مرة أحتاج إلى جلسة تنظيف؟', 'يستفيد كثيرون من جلسة كل ستة أشهر، وقد يقترح الطبيب مدة مختلفة لك.'], ['هل سدّ الشقوق للأطفال فقط؟', 'هو الأكثر شيوعاً لأضراس الأطفال، لكنه قد يفيد بعض البالغين أيضاً.']],
                ],
            ],
        ];
    }
}
