<?php

/**
 * Convert machinery/equipment CSV templates → JSON with Arabic filenames.
 * Usage: php scripts/convert_equipment_csv_to_json.php
 */

use Illuminate\Support\Str;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$baseDir = dirname(__DIR__, 2).'/aalenha_Categories/machinery_equipment';
$outputDir = __DIR__.'/../storage/app/custom_fields_templates/machinery_equipment';

$files = [
    'industrial_equipment/aalenha_equipment_industrial_templates_ar_v1.csv' => 'الصناعة.json',
    'electricity_energy/aalenha_equipment_electricity_energy_templates_ar_v1.csv' => 'الكهرباء_والطاقة.json',
    'construction_equipment/aalenha_equipment_construction_for_rent_templates_ar_v1.csv' => 'معدات_الإنشاءات_للإيجار.json',
    'construction_equipment/aalenha_equipment_construction_for_sale_templates_ar_v1.csv' => 'معدات_الإنشاءات_للبيع.json',
    'construction_equipment/aalenha_equipment_spare_parts_attachments_templates_ar_v1.csv' => 'قطع_الغيار_والملحقات.json',
    'Agricultural Machinery/aalenha_equipment_agricultural_machinery_templates_ar_v1.csv' => 'الآلات_الزراعية.json',
    'Agricultural Machinery/aalenha_equipment_agricultural_machinery_spare_parts_templates_ar_v1.csv' => 'ملحقات_وقطع_غيار_الآلات_الزراعية.json',
];

$descriptions = [
    'الصناعة.json' => 'حقول إعلانات معدات الصناعة',
    'الكهرباء_والطاقة.json' => 'حقول إعلانات الكهرباء والطاقة',
    'معدات_الإنشاءات_للإيجار.json' => 'حقول إعلانات معدات الإنشاءات للإيجار',
    'معدات_الإنشاءات_للبيع.json' => 'حقول إعلانات معدات الإنشاءات للبيع',
    'قطع_الغيار_والملحقات.json' => 'حقول إعلانات قطع الغيار والملحقات',
    'الآلات_الزراعية.json' => 'حقول إعلانات الآلات الزراعية',
    'ملحقات_وقطع_غيار_الآلات_الزراعية.json' => 'حقول إعلانات ملحقات وقطع غيار الآلات الزراعية',
];

require __DIR__.'/equipment_field_translations.php';

if (! is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

$summary = [];

foreach ($files as $relativePath => $jsonName) {
    $csvPath = $baseDir.'/'.$relativePath;
    if (! file_exists($csvPath)) {
        $summary[] = ['file' => $jsonName, 'valid' => false, 'error' => "Missing CSV: {$relativePath}"];
        continue;
    }

    $rows = parseEquipmentCsv($csvPath);
    $fields = array_map('buildEquipmentField', $rows);

    $payload = [
        'version' => 1,
        'description' => $descriptions[$jsonName] ?? 'حقول إعلانات الآلات والمعدات',
        'fields' => $fields,
    ];

    $outPath = $outputDir.'/'.$jsonName;
    file_put_contents(
        $outPath,
        json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
    );

    $result = App\Support\CustomFieldsJsonImporter::parse(file_get_contents($outPath));
    $summary[] = [
        'file' => $jsonName,
        'fields' => count($fields),
        'valid' => $result['error'] === null,
        'error' => $result['error'],
    ];
}

echo json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n";

function parseEquipmentCsv(string $path): array
{
    $rows = [];
    $handle = fopen($path, 'r');
    if ($handle === false) {
        throw new RuntimeException("Cannot open: {$path}");
    }

    $lineNum = 0;
    while (($row = fgetcsv($handle)) !== false) {
        $lineNum++;
        if ($lineNum === 1) {
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
            'options' => splitEquipmentOptions($row[5] ?? ''),
        ];
    }
    fclose($handle);

    return $rows;
}

function splitEquipmentOptions(string $raw): array
{
    $raw = trim($raw);
    if ($raw === '') {
        return [];
    }

    return array_values(array_filter(array_map('trim', preg_split('/[،,]/u', $raw) ?: [])));
}

function buildEquipmentField(array $row): array
{
    global $equipmentLabelTranslations, $equipmentOptionTranslations, $equipmentFieldIds, $equipmentTypeMap;

    $name = $row['name'];
    $csvType = $row['type'];
    $type = $equipmentTypeMap[$csvType] ?? 'text';

    $field = [
        'id' => $equipmentFieldIds[$name] ?? Str::slug($name, '_'),
        'type' => $type,
        'required' => $row['required'],
        'is_active' => true,
        'label' => translateEquipmentLabel($name),
    ];

    if (in_array($csvType, ['قائمة متعددة', 'مجموعة اختيارات'], true)) {
        $field['multiple'] = true;
    }

    if ($type === 'select' && $row['options'] !== []) {
        $field['options'] = array_map(
            fn ($opt) => translateEquipmentOption($opt),
            $row['options']
        );
    }

    return $field;
}

function translateEquipmentLabel(string $ar): array
{
    global $equipmentLabelTranslations;
    $t = $equipmentLabelTranslations[$ar] ?? null;

    return [
        'ar' => $ar,
        'en' => $t['en'] ?? $ar,
        'tr' => $t['tr'] ?? $ar,
    ];
}

function translateEquipmentOption(string $ar): array
{
    global $equipmentOptionTranslations;

    if (isset($equipmentOptionTranslations[$ar])) {
        return ['ar' => $ar, 'en' => $equipmentOptionTranslations[$ar]['en'], 'tr' => $equipmentOptionTranslations[$ar]['tr']];
    }

    if (preg_match('/^(\d{4})$/u', $ar)) {
        return ['ar' => $ar, 'en' => $ar, 'tr' => $ar];
    }

    if (preg_match('/^قبل\s+(\d{4})$/u', $ar, $m)) {
        return [
            'ar' => $ar,
            'en' => "Before {$m[1]}",
            'tr' => "{$m[1]} Öncesi",
        ];
    }

    if (preg_match('/^(\d+)$/u', $ar)) {
        return ['ar' => $ar, 'en' => $ar, 'tr' => $ar];
    }

    return ['ar' => $ar, 'en' => $ar, 'tr' => $ar];
}
