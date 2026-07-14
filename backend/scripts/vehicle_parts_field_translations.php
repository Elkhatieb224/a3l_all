<?php

/** @var array<string, string> */
$vehiclePartsTypeMap = [
    'نص قصير' => 'text',
    'نص طويل' => 'textarea',
    'رقم' => 'number',
    'قائمة منسدلة' => 'select',
    'قائمة متعددة' => 'select',
    'مجموعة اختيارات' => 'select',
    'تاريخ' => 'text',
];

/** @var array<string, string> */
$vehiclePartsFieldIds = [
    'عنوان الإعلان' => 'ad_title',
    'الوصف' => 'description',
    'السعر' => 'price',
    'العملة' => 'currency',
    'حالة المنتج' => 'product_condition',
    'أصلية / تجارية' => 'originality',
    'متوافق مع' => 'compatible_with',
    'رقم القطعة إن وجد' => 'part_number',
    'الكمية' => 'quantity',
    'قابل للتجربة' => 'trial_available',
    'قابل للتفاوض' => 'negotiable',
    'إمكانية الداكيش' => 'delivery_available',
    'صفة المعلن' => 'seller_type',
];

/** @var array<string, array{en: string, tr: string}> */
$vehiclePartsLabelTranslations = [
    'عنوان الإعلان' => ['en' => 'Listing Title', 'tr' => 'İlan Başlığı'],
    'الوصف' => ['en' => 'Description', 'tr' => 'Açıklama'],
    'السعر' => ['en' => 'Price', 'tr' => 'Fiyat'],
    'العملة' => ['en' => 'Currency', 'tr' => 'Para Birimi'],
    'حالة المنتج' => ['en' => 'Condition', 'tr' => 'Ürün Durumu'],
    'أصلية / تجارية' => ['en' => 'Original / Aftermarket', 'tr' => 'Orijinal / Yan Sanayi'],
    'متوافق مع' => ['en' => 'Compatible With', 'tr' => 'Uyumlu Olduğu'],
    'رقم القطعة إن وجد' => ['en' => 'Part Number (if available)', 'tr' => 'Parça Numarası (varsa)'],
    'الكمية' => ['en' => 'Quantity', 'tr' => 'Miktar'],
    'قابل للتجربة' => ['en' => 'Available to Try', 'tr' => 'Deneme İmkanı'],
    'قابل للتفاوض' => ['en' => 'Price Negotiable', 'tr' => 'Pazarlık Payı'],
    'إمكانية الداكيش' => ['en' => 'Delivery Available', 'tr' => 'Teslimat Mevcut'],
    'صفة المعلن' => ['en' => 'Seller Type', 'tr' => 'Satıcı Türü'],
];

/** @var array<string, array{en: string, tr: string}> */
$vehiclePartsOptionTranslations = [
    'دولار' => ['en' => 'US Dollar', 'tr' => 'ABD Doları'],
    'ليرة سورية' => ['en' => 'Syrian Pound', 'tr' => 'Suriye Lirası'],
    'ليرة تركية' => ['en' => 'Turkish Lira', 'tr' => 'Türk Lirası'],
    'جديد' => ['en' => 'New', 'tr' => 'Sıfır'],
    'مستعمل بحالة جيدة' => ['en' => 'Used — Good Condition', 'tr' => 'İyi Durumda İkinci El'],
    'يحتاج صيانة' => ['en' => 'Needs Repair', 'tr' => 'Bakım Gerekiyor'],
    'لا يعمل / للتصليح' => ['en' => 'Not Working / For Repair', 'tr' => 'Çalışmıyor / Tamir İçin'],
    'أصلية' => ['en' => 'Original (OEM)', 'tr' => 'Orijinal (OEM)'],
    'تجارية' => ['en' => 'Aftermarket', 'tr' => 'Yan Sanayi'],
    'غير معروف' => ['en' => 'Unknown', 'tr' => 'Bilinmiyor'],
    'نعم' => ['en' => 'Yes', 'tr' => 'Evet'],
    'لا' => ['en' => 'No', 'tr' => 'Hayır'],
    'لا ينطبق' => ['en' => 'Not Applicable', 'tr' => 'Uygulanmaz'],
    'مالك' => ['en' => 'Private Seller', 'tr' => 'Sahibinden'],
    'تاجر' => ['en' => 'Trader', 'tr' => 'Tüccar'],
    'محل قطع' => ['en' => 'Parts Shop', 'tr' => 'Yedek Parça Mağazası'],
    'شركة' => ['en' => 'Company', 'tr' => 'Şirket'],
];
