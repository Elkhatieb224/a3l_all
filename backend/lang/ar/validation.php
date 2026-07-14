<?php

return [
    'required' => 'حقل :attribute مطلوب.',
    'email' => 'يجب أن يكون :attribute عنوان بريد إلكتروني صالح.',
    'min' => [
        'string' => 'يجب أن يحتوي :attribute على الأقل على :min حرف.',
        'numeric' => 'يجب أن تكون قيمة :attribute على الأقل :min.',
    ],
    'max' => [
        'string' => 'يجب ألا يتجاوز :attribute :max حرف.',
        'numeric' => 'يجب ألا تتجاوز قيمة :attribute :max.',
        'file' => 'يجب ألا يتجاوز حجم :attribute :max كيلوبايت.',
        'array' => 'يجب ألا يحتوي :attribute على أكثر من :max عنصر.',
    ],
    'unique' => ':attribute مُستخدم من قبل.',
    'confirmed' => 'تأكيد :attribute غير متطابق.',
    'string' => 'يجب أن يكون :attribute نصاً.',
    'numeric' => 'يجب أن يكون :attribute رقماً.',
    'integer' => 'يجب أن يكون :attribute عدداً صحيحاً.',
    'in' => ':attribute المحدد غير صالح.',
    'exists' => ':attribute المحدد غير موجود.',
    'image' => 'يجب أن يكون :attribute صورة.',
    'uploaded' => 'لم نتمكّن من رفع :attribute. جرّب صورة أصغر (يفضّل أقل من 5 ميجابايت).',
    'mimes' => 'يجب أن يكون :attribute ملف من نوع: :values.',
    'date' => ':attribute ليس تاريخاً صالحاً.',
    'after' => 'يجب أن يكون :attribute تاريخاً بعد :date.',
    'before' => 'يجب أن يكون :attribute تاريخاً قبل :date.',

    'attributes' => [
        'name' => 'الاسم',
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور',
        'phone' => 'الهاتف',
        'title' => 'العنوان',
        'description' => 'الوصف',
        'price' => 'السعر',
        'location' => 'الموقع',
        'category_id' => 'القسم',
        'image' => 'الصورة',
        'images' => 'صور الإعلان',
        'images.*' => 'صورة الإعلان',
    ],
];

