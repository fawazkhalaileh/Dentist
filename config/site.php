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

    'doctor_name' => ['en' => 'Dr. Moi', 'ar' => 'د. موي'],

    'social' => [
        'instagram' => 'https://www.instagram.com/dr.mohammad.amara/',
        'facebook' => 'https://www.facebook.com/share/1FANuRu5nt/',
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
        'dr-moi' => [
            'widths' => [640, 1280], 'width' => 1280, 'height' => 960,
            'alt' => ['en' => 'Dr. Moi smiling at the clinic reception', 'ar' => 'د. موي مبتسماً في استقبال العيادة'],
        ],
    ],

];
