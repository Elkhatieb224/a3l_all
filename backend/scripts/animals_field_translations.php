<?php

/** @var array<string, string> */
$animalsTypeMap = [
    'نص قصير' => 'text',
    'نص طويل' => 'textarea',
    'رقم' => 'number',
    'قائمة منسدلة' => 'select',
    'قائمة متعددة' => 'select',
    'مجموعة اختيارات' => 'select',
    'تاريخ' => 'text',
];

/** @var array<string, string> */
$animalsFieldIds = [
    'عنوان الإعلان' => 'ad_title',
    'الوصف' => 'description',
    'السعر' => 'price',
    'العملة' => 'currency',
    'العمر' => 'age',
    'الجنس' => 'gender',
    'الحالة الصحية' => 'health_status',
    'مطعّم' => 'vaccinated',
    'أليف / معتاد على التعامل' => 'hand_tame',
    'طريقة التسليم' => 'delivery_method',
    'صفة المعلن' => 'seller_type',
    'قابل للتفاوض' => 'negotiable',
    'حالة المنتج' => 'product_condition',
    'المقاس / الحجم' => 'size',
    'الكمية' => 'quantity',
];

/** @var array<string, array{en: string, tr: string}> */
$animalsLabelTranslations = [
    'عنوان الإعلان' => ['en' => 'Listing Title', 'tr' => 'İlan Başlığı'],
    'الوصف' => ['en' => 'Description', 'tr' => 'Açıklama'],
    'السعر' => ['en' => 'Price', 'tr' => 'Fiyat'],
    'العملة' => ['en' => 'Currency', 'tr' => 'Para Birimi'],
    'العمر' => ['en' => 'Age', 'tr' => 'Yaş'],
    'الجنس' => ['en' => 'Gender', 'tr' => 'Cinsiyet'],
    'الحالة الصحية' => ['en' => 'Health Status', 'tr' => 'Sağlık Durumu'],
    'مطعّم' => ['en' => 'Vaccinated', 'tr' => 'Aşılı'],
    'أليف / معتاد على التعامل' => ['en' => 'Hand-Tame / Socialized', 'tr' => 'Evcil / Alışık'],
    'طريقة التسليم' => ['en' => 'Delivery Method', 'tr' => 'Teslim Yöntemi'],
    'صفة المعلن' => ['en' => 'Seller Type', 'tr' => 'Satıcı Türü'],
    'قابل للتفاوض' => ['en' => 'Price Negotiable', 'tr' => 'Pazarlık Payı'],
    'حالة المنتج' => ['en' => 'Condition', 'tr' => 'Ürün Durumu'],
    'المقاس / الحجم' => ['en' => 'Size', 'tr' => 'Beden / Boyut'],
    'الكمية' => ['en' => 'Quantity', 'tr' => 'Miktar'],
];

/** @var array<string, array{en: string, tr: string}> */
$animalsOptionTranslations = [
    'دولار' => ['en' => 'US Dollar', 'tr' => 'ABD Doları'],
    'ليرة سورية' => ['en' => 'Syrian Pound', 'tr' => 'Suriye Lirası'],
    'ليرة تركية' => ['en' => 'Turkish Lira', 'tr' => 'Türk Lirası'],
    'أقل من شهر' => ['en' => 'Under 1 Month', 'tr' => '1 Aydan Küçük'],
    '1 - 3 أشهر' => ['en' => '1–3 Months', 'tr' => '1–3 Ay'],
    '3 - 6 أشهر' => ['en' => '3–6 Months', 'tr' => '3–6 Ay'],
    '6 - 12 شهر' => ['en' => '6–12 Months', 'tr' => '6–12 Ay'],
    '1 - 3 سنوات' => ['en' => '1–3 Years', 'tr' => '1–3 Yıl'],
    'أكثر من 3 سنوات' => ['en' => 'Over 3 Years', 'tr' => '3 Yıldan Büyük'],
    'غير معروف' => ['en' => 'Unknown', 'tr' => 'Bilinmiyor'],
    'ذكر' => ['en' => 'Male', 'tr' => 'Erkek'],
    'أنثى' => ['en' => 'Female', 'tr' => 'Dişi'],
    'ممتازة' => ['en' => 'Excellent', 'tr' => 'Mükemmel'],
    'جيدة' => ['en' => 'Good', 'tr' => 'İyi'],
    'تحتاج رعاية' => ['en' => 'Needs Care', 'tr' => 'Bakım Gerekiyor'],
    'نعم' => ['en' => 'Yes', 'tr' => 'Evet'],
    'لا' => ['en' => 'No', 'tr' => 'Hayır'],
    'جزئيًا' => ['en' => 'Partially', 'tr' => 'Kısmen'],
    'استلام من البائع' => ['en' => 'Pickup from Seller', 'tr' => 'Satıcıdan Teslim'],
    'توصيل داخل المدينة' => ['en' => 'City Delivery', 'tr' => 'Şehir İçi Teslimat'],
    'مالك' => ['en' => 'Private Seller', 'tr' => 'Sahibinden'],
    'مربي' => ['en' => 'Breeder', 'tr' => 'Üretici / Yetiştirici'],
    'محل / متجر' => ['en' => 'Shop / Store', 'tr' => 'Mağaza'],
    'وسيط' => ['en' => 'Broker', 'tr' => 'Aracı'],
    'جديد' => ['en' => 'New', 'tr' => 'Sıfır'],
    'مستعمل بحالة جيدة' => ['en' => 'Used — Good Condition', 'tr' => 'İyi Durumda İkinci El'],
    'يحتاج صيانة' => ['en' => 'Needs Repair', 'tr' => 'Bakım Gerekiyor'],
    'صغير' => ['en' => 'Small', 'tr' => 'Küçük'],
    'متوسط' => ['en' => 'Medium', 'tr' => 'Orta'],
    'كبير' => ['en' => 'Large', 'tr' => 'Büyük'],
    'متعدد المقاسات' => ['en' => 'Multiple Sizes', 'tr' => 'Çoklu Beden'],
    'لا ينطبق' => ['en' => 'Not Applicable', 'tr' => 'Uygulanmaz'],
];
