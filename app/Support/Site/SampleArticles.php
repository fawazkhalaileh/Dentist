<?php

namespace App\Support\Site;

/**
 * Bodies (Markdown) of the sample articles listed in SampleContent::posts().
 * General dental education only; shown with a "sample article" notice and not indexed.
 */
final class SampleArticles
{
    /**
     * @return array<string, array{en: string, ar: string}>
     */
    public static function bodies(): array
    {
        return [
            'how-often-check-up' => [
                'en' => <<<'MD'
Regular check-ups are one of the simplest ways to keep your teeth healthy. Problems such as decay and gum disease often start without pain, so a dentist can spot them long before you notice anything.

## What happens at a check-up?

Your dentist will ask about your general health, look closely at your teeth, gums and the soft tissues of your mouth, and check any existing fillings or crowns. X-rays are taken only when they help.

## How often should you go?

Many people are seen every six months, but there is no single rule. Someone with healthy teeth and gums may need longer between visits, while someone with gum problems may benefit from seeing the dentist more often. Your dentist will recommend an interval that suits you.

## Between visits

- Brush twice a day with a fluoride toothpaste
- Clean between your teeth once a day
- Keep sugary snacks and drinks to mealtimes

If you notice pain, swelling or bleeding gums, don't wait for your next check-up: get in touch.
MD,
                'ar' => <<<'MD'
الفحص الدوري من أبسط الطرق للحفاظ على صحة أسنانك. فالمشكلات مثل التسوس وأمراض اللثة تبدأ غالباً دون ألم، ويستطيع الطبيب اكتشافها قبل أن تلاحظ أي شيء بوقت طويل.

## ماذا يحدث أثناء الفحص؟

يسألك الطبيب عن صحتك العامة، ويفحص أسنانك ولثتك وأنسجة الفم بعناية، ويتحقق من الحشوات أو التيجان الموجودة. ولا تُؤخذ صور الأشعة إلا عند الحاجة.

## كم مرة يجب أن تزور الطبيب؟

يُفحص كثيرون كل ستة أشهر، لكن لا توجد قاعدة واحدة. فمن لديه أسنان ولثة سليمة قد يحتاج إلى فترة أطول بين الزيارات، بينما يستفيد من لديه مشكلات في اللثة من زيارات أكثر. وسيقترح عليك الطبيب الموعد المناسب.

## بين الزيارات

- نظّف أسنانك مرتين يومياً بمعجون يحتوي على الفلورايد
- نظّف بين أسنانك مرة يومياً
- اجعل الوجبات الخفيفة والمشروبات السكرية مع الوجبات الرئيسية

إذا لاحظت ألماً أو تورماً أو نزفاً في اللثة فلا تنتظر موعدك القادم، وتواصل معنا.
MD,
            ],
            'whitening-what-to-know' => [
                'en' => <<<'MD'
A brighter smile is one of the most common requests at the dentist. Professional whitening can make a real difference, but it helps to know what to expect.

## Who is whitening for?

Whitening works best on natural teeth that have yellowed with age or been stained by coffee, tea or smoking. It does not change the colour of fillings, crowns or veneers, so these may need replacing afterwards to match.

## Professional or shop-bought?

Products supervised by a dentist are tailored to your teeth and checked for safety. Your dentist will also make sure there are no problems, such as decay or gum disease, that should be treated first.

## Sensitivity

Some people feel short-lived sensitivity during whitening. It usually settles quickly, and your dentist can suggest ways to reduce it.

## Keeping your results

Results often last a year or more. Rinsing after dark drinks, not smoking and keeping up your hygiene visits all help.
MD,
                'ar' => <<<'MD'
الابتسامة الأكثر إشراقاً من أكثر الطلبات شيوعاً عند طبيب الأسنان. ويمكن للتبييض الاحترافي أن يُحدث فرقاً حقيقياً، ومن المفيد أن تعرف ما تتوقعه.

## لمن يناسب التبييض؟

ينجح التبييض أكثر على الأسنان الطبيعية التي اصفرّت مع العمر أو تصبغت بالقهوة أو الشاي أو التدخين. ولا يغيّر لون الحشوات أو التيجان أو القشور، لذا قد تحتاج إلى استبدالها لاحقاً لتتطابق.

## احترافي أم تجاري؟

المنتجات التي يشرف عليها الطبيب تُضبط لتناسب أسنانك ويُتحقق من سلامتها. كما يتأكد الطبيب من عدم وجود مشكلات مثل التسوس أو أمراض اللثة يجب علاجها أولاً.

## الحساسية

يشعر البعض بحساسية مؤقتة أثناء التبييض، وتزول عادةً بسرعة، ويمكن للطبيب اقتراح طرق لتخفيفها.

## الحفاظ على النتيجة

تدوم النتيجة غالباً سنة أو أكثر. ويساعد على ذلك المضمضة بعد المشروبات الداكنة، والامتناع عن التدخين، والمواظبة على جلسات التنظيف.
MD,
            ],
            'veneers-or-crowns' => [
                'en' => <<<'MD'
Veneers and crowns can both transform the look of a smile, but they are designed for different situations.

## Veneers

A veneer is a thin facing bonded to the front of a tooth. It is usually chosen to improve colour, shape or small gaps on teeth that are otherwise healthy. Little or no tooth needs to be removed.

## Crowns

A crown covers the whole tooth. It is often the better choice when a tooth is weakened, cracked, heavily filled or has had root canal treatment, because it protects the tooth as well as improving its appearance.

## How to choose

The right option depends on the health and strength of each tooth, your bite and the result you would like. A consultation with photographs and a careful examination is the best place to start.
MD,
                'ar' => <<<'MD'
يمكن لكلٍّ من القشور والتيجان أن يغيّر مظهر الابتسامة، لكنهما مصممان لحالات مختلفة.

## القشور التجميلية

القشرة طبقة رقيقة تُثبّت على الواجهة الأمامية للسن، وتُختار عادةً لتحسين اللون أو الشكل أو الفراغات الصغيرة في أسنان سليمة. ولا يُزال من السن إلا القليل أو لا شيء.

## التيجان

يغطي التاج السن بالكامل، وغالباً يكون الخيار الأفضل عندما يكون السن ضعيفاً أو متشققاً أو كثير الحشوات أو معالجاً عصبياً، لأنه يحمي السن إلى جانب تحسين مظهره.

## كيف تختار؟

يعتمد الخيار المناسب على صحة كل سن وقوته، وعلى إطباقك والنتيجة التي ترغب بها. وأفضل بداية هي استشارة مع صور وفحص دقيق.
MD,
            ],
            'implants-explained' => [
                'en' => <<<'MD'
Dental implants are a long-term way to replace missing teeth. Here is what the treatment usually involves.

## 1. Consultation and planning

Your dentist examines your mouth and uses X-rays or a 3D scan to check the bone. Together you discuss whether an implant is suitable and what it will support: a crown, a bridge or a denture.

## 2. Placing the implant

The implant is placed in the jaw, usually under local anaesthetic. Many patients find it more comfortable than they expected.

## 3. Healing

Over the following weeks or months the implant bonds with the bone. A temporary tooth can often be worn in the meantime.

## 4. The final tooth

Once healed, the final crown, bridge or denture is made and fitted to match your other teeth.

## Looking after implants

Brush and clean around implants as carefully as natural teeth, and keep regular check-ups.
MD,
                'ar' => <<<'MD'
زراعة الأسنان حل طويل الأمد لتعويض الأسنان المفقودة. وإليك ما يتضمنه العلاج عادةً.

## 1. الاستشارة والتخطيط

يفحص الطبيب فمك ويستخدم الأشعة أو المسح ثلاثي الأبعاد لتقييم العظم. وتناقشان معاً مدى ملاءمة الزرعة وما ستحمله: تاجاً أو جسراً أو طقماً.

## 2. وضع الزرعة

توضع الزرعة في الفك عادةً تحت تخدير موضعي، ويجدها كثير من المراجعين أكثر راحة مما توقعوا.

## 3. الالتئام

خلال الأسابيع أو الأشهر التالية تلتحم الزرعة بالعظم، ويمكن غالباً استخدام سن مؤقت خلال هذه الفترة.

## 4. السن النهائي

بعد الالتئام يُصنع التاج أو الجسر أو الطقم النهائي ويُركّب ليطابق أسنانك الأخرى.

## العناية بالزرعات

نظّف حول الزرعات بعناية كما تنظف أسنانك الطبيعية، وواظب على الفحص الدوري.
MD,
            ],
            'sensitive-teeth' => [
                'en' => <<<'MD'
A sharp twinge when you drink something cold is very common. Sensitivity usually means the inner layer of the tooth, the dentine, is less protected than it should be.

## Common causes

- Brushing too hard, which can wear enamel and gums
- Gum recession exposing the root surface
- Acidic food and drinks
- A cracked tooth or a worn filling
- Short-term sensitivity after whitening

## Simple things that help

Use a soft brush and a toothpaste for sensitive teeth, brush gently and avoid brushing straight after acidic drinks.

## When to see your dentist

If sensitivity lasts more than a few days, keeps getting worse, or comes with pain when biting, book a check-up so the cause can be found and treated.
MD,
                'ar' => <<<'MD'
الشعور بوخزة حادة عند شرب شيء بارد أمر شائع جداً. وتعني الحساسية عادةً أن الطبقة الداخلية من السن، أي العاج، أقل حماية مما ينبغي.

## أسباب شائعة

- التنظيف بقوة زائدة مما قد يؤدي إلى تآكل المينا واللثة
- انحسار اللثة وانكشاف سطح الجذر
- الأطعمة والمشروبات الحمضية
- سن متشقق أو حشوة متآكلة
- حساسية مؤقتة بعد التبييض

## خطوات بسيطة تساعد

استخدم فرشاة ناعمة ومعجوناً للأسنان الحساسة، ونظّف برفق، وتجنب التنظيف مباشرة بعد المشروبات الحمضية.

## متى تزور الطبيب؟

إذا استمرت الحساسية أكثر من بضعة أيام أو ازدادت سوءاً أو رافقها ألم عند العض، فاحجز موعداً للفحص لمعرفة السبب وعلاجه.
MD,
            ],
            'first-visit-nerves' => [
                'en' => <<<'MD'
Feeling nervous about the dentist is very common, and it is nothing to be embarrassed about. A few simple steps can make visits much easier.

## Tell us how you feel

Let the team know when you book and again in the chair. Knowing you are anxious helps your dentist explain each step and go at your pace.

## Agree a stop signal

Raising a hand is a simple way to ask for a pause at any time.

## Start small

A first visit can be just a conversation and a look, with no treatment at all. Building trust first often makes later appointments easier.

## Little things that help

Bring headphones and music, choose a time of day when you feel calm, and ask as many questions as you like.
MD,
                'ar' => <<<'MD'
الشعور بالقلق من زيارة طبيب الأسنان شائع جداً ولا داعي للخجل منه. ويمكن لخطوات بسيطة أن تجعل الزيارات أسهل بكثير.

## أخبرنا بما تشعر به

أخبر الفريق عند الحجز ومرة أخرى على كرسي العلاج. فمعرفة قلقك تساعد الطبيب على شرح كل خطوة والسير بالوتيرة التي تناسبك.

## اتفق على إشارة للتوقف

رفع اليد طريقة بسيطة لطلب استراحة في أي وقت.

## ابدأ بخطوة صغيرة

يمكن أن تكون الزيارة الأولى مجرد حديث وفحص دون أي علاج. فبناء الثقة أولاً يجعل المواعيد اللاحقة أسهل غالباً.

## أمور صغيرة تساعد

أحضر سماعات وموسيقى، واختر وقتاً من اليوم تشعر فيه بالهدوء، واسأل ما شئت من أسئلة.
MD,
            ],
        ];
    }
}
