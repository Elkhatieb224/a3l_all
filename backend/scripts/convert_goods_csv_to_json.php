<?php

/**
 * One-off converter: goods_new_used CSV templates → custom fields JSON.
 * Usage: php scripts/convert_goods_csv_to_json.php
 */

use Illuminate\Support\Str;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$sourceDir = dirname(__DIR__, 2).'/aalenha_Categories/goods_new_used/aalenha_goods_new_used_templates_ar_v1';
$outputDir = __DIR__.'/../storage/app/custom_fields_templates/goods_new_used';

$files = [
    'الأجهزة والإلكترونيات-Table 1.csv' => 'الأجهزة_والإلكترونيات.json',
    'التحف والمقتنيات-Table 1.csv' => 'التحف_والمقتنيات.json',
    'السلع العامة-Table 1.csv' => 'السلع_العامة.json',
    'الملابس والإكسسوارات-Table 1.csv' => 'الملابس_والإكسسوارات.json',
    'المنتجات الاستهلاكية-Table 1.csv' => 'المنتجات_الاستهلاكية.json',
    'المنزل والديكور-Table 1.csv' => 'المنزل_والديكور.json',
];

$descriptions = [
    'الأجهزة_والإلكترونيات.json' => 'حقول إعلانات الأجهزة والإلكترونيات (سلع جديدة ومستعملة)',
    'التحف_والمقتنيات.json' => 'حقول إعلانات التحف والمقتنيات والمجوهرات',
    'السلع_العامة.json' => 'حقول إعلانات السلع العامة (كتب، هوايات، رياضة، وغيرها)',
    'الملابس_والإكسسوارات.json' => 'حقول إعلانات الملابس والإكسسوارات',
    'المنتجات_الاستهلاكية.json' => 'حقول إعلانات المنتجات الاستهلاكية (أغذية، عناية شخصية)',
    'المنزل_والديكور.json' => 'حقول إعلانات المنزل والديكور',
];

/** @var array<string, array{en: string, tr: string}> */
$labelTranslations = [
    'عنوان الإعلان' => ['en' => 'Listing Title', 'tr' => 'İlan Başlığı'],
    'الوصف' => ['en' => 'Description', 'tr' => 'Açıklama'],
    'السعر' => ['en' => 'Price', 'tr' => 'Fiyat'],
    'العملة' => ['en' => 'Currency', 'tr' => 'Para Birimi'],
    'حالة المنتج' => ['en' => 'Condition', 'tr' => 'Ürün Durumu'],
    'الضمان' => ['en' => 'Warranty', 'tr' => 'Garanti'],
    'الملحقات' => ['en' => 'Included Accessories', 'tr' => 'Aksesuarlar'],
    'قابل للتجربة' => ['en' => 'Available to Try', 'tr' => 'Deneme İmkanı'],
    'قابل للتفاوض' => ['en' => 'Price Negotiable', 'tr' => 'Pazarlık Payı'],
    'إمكانية الداكيش' => ['en' => 'Delivery Available', 'tr' => 'Teslimat Mevcut'],
    'صفة المعلن' => ['en' => 'Seller Type', 'tr' => 'Satıcı Türü'],
    'الأصالة' => ['en' => 'Authenticity', 'tr' => 'Orijinallik'],
    'مادة الصنع' => ['en' => 'Material', 'tr' => 'Malzeme'],
    'شهادة / فاتورة' => ['en' => 'Certificate / Invoice', 'tr' => 'Sertifika / Fatura'],
    'العمر التقريبي' => ['en' => 'Approximate Age', 'tr' => 'Tahmini Yaş'],
    'الكمية' => ['en' => 'Quantity', 'tr' => 'Miktar'],
    'المقاس' => ['en' => 'Size', 'tr' => 'Beden / Numara'],
    'اللون' => ['en' => 'Color', 'tr' => 'Renk'],
    'الفئة' => ['en' => 'Category', 'tr' => 'Kategori'],
    'تاريخ الصلاحية' => ['en' => 'Expiry Date', 'tr' => 'Son Kullanma Tarihi'],
    'بلد المنشأ' => ['en' => 'Country of Origin', 'tr' => 'Menşei Ülke'],
    'الأبعاد التقريبية' => ['en' => 'Approximate Dimensions', 'tr' => 'Tahmini Ölçüler'],
];

/** @var array<string, array{en: string, tr: string}> */
$optionTranslations = [
    'دولار' => ['en' => 'US Dollar', 'tr' => 'ABD Doları'],
    'ليرة سورية' => ['en' => 'Syrian Pound', 'tr' => 'Suriye Lirası'],
    'ليرة تركية' => ['en' => 'Turkish Lira', 'tr' => 'Türk Lirası'],
    'جديد' => ['en' => 'New', 'tr' => 'Sıfır'],
    'مستعمل' => ['en' => 'Used', 'tr' => 'İkinci El'],
    'يحتاج صيانة' => ['en' => 'Needs Repair', 'tr' => 'Bakım Gerekiyor'],
    'شبه جديد' => ['en' => 'Like New', 'tr' => 'Az Kullanılmış'],
    'مستعمل بحالة جيدة' => ['en' => 'Used — Good Condition', 'tr' => 'İyi Durumda İkinci El'],
    'غير مفتوح' => ['en' => 'Sealed', 'tr' => 'Açılmamış'],
    'مفتوح' => ['en' => 'Opened', 'tr' => 'Açılmış'],
    'يوجد ضمان' => ['en' => 'Under Warranty', 'tr' => 'Garantili'],
    'لا يوجد ضمان' => ['en' => 'No Warranty', 'tr' => 'Garantisiz'],
    'غير معروف' => ['en' => 'Unknown', 'tr' => 'Bilinmiyor'],
    'شاحن' => ['en' => 'Charger', 'tr' => 'Şarj Aleti'],
    'كابل' => ['en' => 'Cable', 'tr' => 'Kablo'],
    'علبة أصلية' => ['en' => 'Original Box', 'tr' => 'Orijinal Kutu'],
    'ريموت' => ['en' => 'Remote Control', 'tr' => 'Kumanda'],
    'بطارية' => ['en' => 'Battery', 'tr' => 'Pil'],
    'حقيبة' => ['en' => 'Bag / Case', 'tr' => 'Çanta / Kılıf'],
    'لا يوجد' => ['en' => 'None', 'tr' => 'Yok'],
    'نعم' => ['en' => 'Yes', 'tr' => 'Evet'],
    'لا' => ['en' => 'No', 'tr' => 'Hayır'],
    'مالك' => ['en' => 'Private Seller', 'tr' => 'Sahibinden'],
    'متجر' => ['en' => 'Shop', 'tr' => 'Mağaza'],
    'شركة' => ['en' => 'Company', 'tr' => 'Şirket'],
    'وكيل / مندوب' => ['en' => 'Agent / Dealer', 'tr' => 'Temsilci / Bayi'],
    'أصلي' => ['en' => 'Original', 'tr' => 'Orijinal'],
    'نسخة / تقليد' => ['en' => 'Replica / Imitation', 'tr' => 'Replika / Taklit'],
    'لا ينطبق' => ['en' => 'Not Applicable', 'tr' => 'Uygulanmaz'],
    'مقاس حر' => ['en' => 'Free Size', 'tr' => 'Tek Beden'],
    'رجالي' => ['en' => 'Men', 'tr' => 'Erkek'],
    'نسائي' => ['en' => 'Women', 'tr' => 'Kadın'],
    'أطفال' => ['en' => 'Kids', 'tr' => 'Çocuk'],
    'للجنسين' => ['en' => 'Unisex', 'tr' => 'Unisex'],
];

/** @var array<string, string> */
$fieldIds = [
    'عنوان الإعلان' => 'ad_title',
    'الوصف' => 'description',
    'السعر' => 'price',
    'العملة' => 'currency',
    'حالة المنتج' => 'product_condition',
    'الضمان' => 'warranty',
    'الملحقات' => 'accessories',
    'قابل للتجربة' => 'trial_available',
    'قابل للتفاوض' => 'negotiable',
    'إمكانية الداكيش' => 'delivery_available',
    'صفة المعلن' => 'seller_type',
    'الأصالة' => 'authenticity',
    'مادة الصنع' => 'material',
    'شهادة / فاتورة' => 'certificate_invoice',
    'العمر التقريبي' => 'approximate_age',
    'الكمية' => 'quantity',
    'المقاس' => 'size',
    'اللون' => 'color',
    'الفئة' => 'category',
    'تاريخ الصلاحية' => 'expiry_date',
    'بلد المنشأ' => 'country_of_origin',
    'الأبعاد التقريبية' => 'approximate_dimensions',
];

/** @var array<string, string> */
$typeMap = [
    'نص قصير' => 'text',
    'نص طويل' => 'textarea',
    'رقم' => 'number',
    'قائمة منسدلة' => 'select',
    'قائمة متعددة' => 'select',
    'تاريخ' => 'text',
];

function splitOptions(string $raw): array
{
    $raw = trim($raw);
    if ($raw === '') {
        return [];
    }

    return array_values(array_filter(array_map('trim', preg_split('/[،,]/u', $raw) ?: [])));
}

function translateLabel(string $ar, array $map): array
{
    $t = $map[$ar] ?? null;

    return [
        'ar' => $ar,
        'en' => $t['en'] ?? $ar,
        'tr' => $t['tr'] ?? $ar,
    ];
}

function translateOption(string $ar, array $map): array
{
    if (isset($map[$ar])) {
        return ['ar' => $ar, 'en' => $map[$ar]['en'], 'tr' => $map[$ar]['tr']];
    }

    // Sizes, shoe numbers, and age labels.
    if (preg_match('/^(XS|S|M|L|XL|XXL|3XL|4XL|5XL|\d+)$/u', $ar)) {
        return ['ar' => $ar, 'en' => $ar, 'tr' => $ar];
    }

    if (preg_match('/^(\d+)-(\d+)\s*أشهر$/u', $ar, $m)) {
        return [
            'ar' => $ar,
            'en' => "{$m[1]}–{$m[2]} months",
            'tr' => "{$m[1]}–{$m[2]} ay",
        ];
    }

    if (preg_match('/^(\d+)\s*سنة$/u', $ar, $m)) {
        return [
            'ar' => $ar,
            'en' => "{$m[1]} year".($m[1] === '1' ? '' : 's'),
            'tr' => "{$m[1]} yaş",
        ];
    }

    if (preg_match('/^(\d+)\s*سنوات$/u', $ar, $m)) {
        return [
            'ar' => $ar,
            'en' => "{$m[1]} years",
            'tr' => "{$m[1]} yaş",
        ];
    }

    if (preg_match('/^(\d+)\s*شهر$/u', $ar, $m)) {
        return [
            'ar' => $ar,
            'en' => "{$m[1]} month".($m[1] === '1' ? '' : 's'),
            'tr' => "{$m[1]} ay",
        ];
    }

    return ['ar' => $ar, 'en' => $ar, 'tr' => $ar];
}

function parseCsv(string $path): array
{
    $rows = [];
    $handle = fopen($path, 'r');
    if ($handle === false) {
        throw new RuntimeException("Cannot open: {$path}");
    }

    $header = fgetcsv($handle);
    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) < 4) {
            continue;
        }
        $name = trim($row[1] ?? '');
        if ($name === '') {
            continue;
        }
        $rows[] = [
            'name' => $name,
            'type' => trim($row[2] ?? ''),
            'required' => trim($row[3] ?? '') === 'نعم',
            'options' => splitOptions($row[5] ?? ''),
        ];
    }
    fclose($handle);

    return $rows;
}

function buildField(array $row, array $labelTranslations, array $optionTranslations, array $fieldIds, array $typeMap): array
{
    $name = $row['name'];
    $csvType = $row['type'];
    $type = $typeMap[$csvType] ?? 'text';

    $field = [
        'id' => $fieldIds[$name] ?? Str::slug($name, '_'),
        'type' => $type,
        'required' => $row['required'],
        'is_active' => true,
        'label' => translateLabel($name, $labelTranslations),
    ];

    if ($csvType === 'قائمة متعددة') {
        $field['multiple'] = true;
    }

    if ($type === 'number' && $name === 'السعر') {
        $field['show_currency'] = false;
    }

    if ($type === 'select' && $row['options'] !== []) {
        $field['options'] = array_map(
            fn ($opt) => translateOption($opt, $optionTranslations),
            $row['options']
        );
    }

    return $field;
}

if (! is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

$summary = [];

foreach ($files as $csvName => $jsonName) {
    $csvPath = $sourceDir.'/'.$csvName;
    if (! file_exists($csvPath)) {
        fwrite(STDERR, "Missing: {$csvPath}\n");
        continue;
    }

    $rows = parseCsv($csvPath);
    $fields = array_map(
        fn ($row) => buildField($row, $labelTranslations, $optionTranslations, $fieldIds, $typeMap),
        $rows
    );

    $payload = [
        'version' => 1,
        'description' => $descriptions[$jsonName],
        'fields' => $fields,
    ];

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $outPath = $outputDir.'/'.$jsonName;
    file_put_contents($outPath, $json."\n");

    $raw = file_get_contents($outPath);
    $result = App\Support\CustomFieldsJsonImporter::parse($raw);
    $summary[] = [
        'file' => $jsonName,
        'fields' => count($fields),
        'valid' => $result['error'] === null,
        'error' => $result['error'],
    ];
}

echo json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n";
