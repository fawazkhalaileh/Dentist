<?php

/*
|--------------------------------------------------------------------------
| Public website presentation
|--------------------------------------------------------------------------
| Photos and links used by the website design. Photos live in
| public/images/site as <key>-<width>.webp. Patient before/after images are
| never listed here: they are published through the Gallery (with consent).
*/

return [

    'doctor_name' => ['en' => 'Dr. Mohammad Amara', 'ar' => 'د. محمد عماره'],
    // Shorter form for the navigation.
    'doctor_short_name' => ['en' => 'Dr. Amara', 'ar' => 'د. عماره'],

    /*
    | Lining up before/after photos in the comparison slider. Per media id: where the
    | smile sits in that photo, as fractions of the image (cx, cy = centre of the teeth;
    | w = mouth width, corner to corner). The slider zooms and shifts each photo on
    | display so both smiles match; the photos themselves are never changed.
    | Measured by hand for the cases published on 2026-11-03.
    */
    'smile_alignment' => [
        20 => ['cx' => 0.500, 'cy' => 0.43, 'w' => 0.42], 21 => ['cx' => 0.505, 'cy' => 0.35, 'w' => 0.43],
        13 => ['cx' => 0.515, 'cy' => 0.28, 'w' => 0.47], 12 => ['cx' => 0.500, 'cy' => 0.35, 'w' => 0.60],
        16 => ['cx' => 0.500, 'cy' => 0.385, 'w' => 0.40], 15 => ['cx' => 0.510, 'cy' => 0.35, 'w' => 0.42],
        18 => ['cx' => 0.500, 'cy' => 0.40, 'w' => 0.64], 17 => ['cx' => 0.515, 'cy' => 0.32, 'w' => 0.59],
        4 => ['cx' => 0.485, 'cy' => 0.41, 'w' => 0.73], 3 => ['cx' => 0.490, 'cy' => 0.43, 'w' => 0.62],
    ],

    'social' => [
        'instagram' => 'https://www.instagram.com/dr.mohammad.amara/',
        'facebook' => 'https://www.facebook.com/share/1FANuRu5nt/',
    ],

    // Owner-supplied guide prices in whole Jordanian dinars.
    'prices' => [
        [
            'label' => ['en' => 'Orthodontics', 'ar' => 'تقويم الأسنان'],
            'items' => [
                ['label' => ['en' => 'Clear aligners', 'ar' => 'التقويم الشفاف'], 'amount' => 1500, 'from' => true, 'treatment' => 'alignment', 'note' => ['en' => 'Depends on the complexity of the case', 'ar' => 'حسب تعقيد الحالة']],
                ['label' => ['en' => 'Traditional braces', 'ar' => 'التقويم التقليدي'], 'amount' => 800, 'from' => true, 'treatment' => 'alignment'],
            ],
        ],
        [
            'label' => ['en' => 'Fillings', 'ar' => 'حشوات الأسنان'],
            'items' => [
                ['label' => ['en' => 'Composite (white) fillings/restorations', 'ar' => 'حشوات وترميمات الكومبوزيت (البيضاء)'], 'amount' => 30, 'from' => true, 'treatment' => 'general'],
            ],
        ],
        [
            'label' => ['en' => 'Cleaning', 'ar' => 'تنظيف الأسنان'],
            'items' => [
                ['label' => ['en' => 'Traditional cleaning', 'ar' => 'التنظيف التقليدي'], 'amount' => 30, 'from' => false, 'treatment' => 'preventive'],
                ['label' => ['en' => 'GBT (Guided Biofilm Therapy)', 'ar' => 'تنظيف الأسنان بتقنية GBT (العلاج الموجّه للغشاء الحيوي)'], 'amount' => 50, 'from' => false, 'treatment' => 'preventive'],
            ],
        ],
        [
            'label' => ['en' => 'Whitening', 'ar' => 'تبييض الأسنان'],
            'items' => [
                ['label' => ['en' => 'In-clinic whitening + traditional cleaning', 'ar' => 'تبييض الأسنان في العيادة مع التنظيف التقليدي'], 'amount' => 150, 'from' => false, 'treatment' => 'whitening'],
                ['label' => ['en' => 'In-clinic whitening + GBT cleaning', 'ar' => 'تبييض الأسنان في العيادة مع تنظيف GBT'], 'amount' => 170, 'from' => false, 'treatment' => 'whitening'],
                ['label' => ['en' => 'Home whitening', 'ar' => 'التبييض المنزلي'], 'amount' => 190, 'from' => false, 'treatment' => 'whitening'],
            ],
        ],
        [
            'label' => ['en' => 'Veneers & crowns', 'ar' => 'القشور والتيجان'],
            'items' => [
                ['label' => ['en' => 'E.max veneers', 'ar' => 'قشور إيماكس'], 'amount' => 180, 'from' => false, 'treatment' => 'veneers', 'note' => ['en' => 'Per tooth', 'ar' => 'للسن الواحد']],
                ['label' => ['en' => 'Zirconia crowns', 'ar' => 'تيجان الزركونيا'], 'amount' => 180, 'from' => false, 'treatment' => 'crowns', 'note' => ['en' => 'Per tooth', 'ar' => 'للسن الواحد']],
            ],
        ],
        [
            'label' => ['en' => 'Implants', 'ar' => 'زراعة الأسنان'],
            'items' => [
                ['label' => ['en' => 'Single implant', 'ar' => 'زراعة سن واحد'], 'amount' => 500, 'from' => true, 'treatment' => 'implants', 'note' => ['en' => 'Per tooth, including the zirconia crown', 'ar' => 'للسن الواحد، شاملاً تاج الزركونيا']],
                ['label' => ['en' => 'All-on-X (full-arch implants)', 'ar' => 'زراعة فك كامل بتقنية All-on-X'], 'amount' => null, 'from' => false, 'treatment' => 'implants'],
            ],
        ],
    ],

    'photos' => [
        'reception' => [
            'widths' => [480, 800, 1200], 'width' => 1200, 'height' => 1600,
            'alt' => ['en' => 'The clinic reception with plants and soft lighting', 'ar' => 'استقبال العيادة مع النباتات والإضاءة الهادئة'],
        ],
        'treatment-room' => [
            'widths' => [640, 1024, 1600], 'width' => 1600, 'height' => 1200,
            'alt' => ['en' => 'A bright treatment room at the clinic', 'ar' => 'غرفة علاج مشرقة في العيادة'],
        ],
        'doctor' => [
            'widths' => [640, 1280], 'width' => 1280, 'height' => 960,
            'alt' => ['en' => 'Dr. Mohammad Amara smiling at the clinic reception', 'ar' => 'د. محمد عماره مبتسماً في استقبال العيادة'],
        ],
    ],

];
