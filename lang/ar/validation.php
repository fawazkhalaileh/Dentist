<?php

/*
| Arabic validation messages for the rules used on public forms.
| Draft for native-speaker review. Rules not listed fall back to English.
*/

return [
    'required' => 'حقل :attribute مطلوب.',
    'required_without' => 'حقل :attribute مطلوب عند عدم إدخال :values.',
    'email' => 'يجب أن يكون :attribute عنوان بريد إلكتروني صالحاً.',
    'string' => 'يجب أن يكون :attribute نصاً.',
    'max' => [
        'string' => 'يجب ألا يزيد :attribute عن :max حرفاً.',
    ],
    'min' => [
        'string' => 'يجب ألا يقل :attribute عن :min أحرف.',
    ],
    'confirmed' => 'تأكيد :attribute غير مطابق.',
    'prohibited' => 'لا يُسمح بحقل :attribute.',
    'password' => [
        'letters' => 'يجب أن يحتوي :attribute على حرف واحد على الأقل.',
        'mixed' => 'يجب أن يحتوي :attribute على حرف كبير وحرف صغير على الأقل.',
        'numbers' => 'يجب أن يحتوي :attribute على رقم واحد على الأقل.',
        'symbols' => 'يجب أن يحتوي :attribute على رمز واحد على الأقل.',
        'uncompromised' => 'ظهرت كلمة المرور هذه في تسريب بيانات. يرجى اختيار كلمة مرور مختلفة.',
    ],

    'attributes' => [
        'name' => 'الاسم',
        'email' => 'البريد الإلكتروني',
        'phone' => 'الهاتف',
        'message' => 'الرسالة',
        'password' => 'كلمة المرور',
    ],
];
