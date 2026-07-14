<?php

/**
 * Convert vehicles CSV templates → JSON with Arabic filenames.
 * Usage: php scripts/convert_vehicles_csv_to_json.php
 */

use Illuminate\Support\Str;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$sourceDir = dirname(__DIR__, 2).'/aalenha_Categories/vehicles/aalenha_vehicles_8_templates_ar_v1';
$outputDir = __DIR__.'/../storage/app/custom_fields_templates/vehicles';

/** CSV basename (without prefix/suffix) => Arabic JSON filename */
$files = [
    '01_السيارات_العام-Table 1.csv' => 'السيارات_العام.json',
    '02_السيارات_الكهربائية-Table 1.csv' => 'السيارات_الكهربائية.json',
    '03_دراجات_ATV_UTV-Table 1.csv' => 'دراجات_ATV_UTV.json',
    '04_فانات_وتجاري-Table 1.csv' => 'فانات_وتجاري.json',
    '05_مركبات_متضررة-Table 1.csv' => 'مركبات_متضررة.json',
    '06_كرفانات-Table 1.csv' => 'كرفانات.json',
    '07_مركبات_بحرية-Table 1.csv' => 'مركبات_بحرية.json',
    '08_مركبات_جوية-Table 1.csv' => 'مركبات_جوية.json',
];

$descriptions = [
    'السيارات_العام.json' => 'حقول إعلانات السيارات العامة (سيارات، SUV، بيك آب، كلاسيكية)',
    'السيارات_الكهربائية.json' => 'حقول إعلانات السيارات الكهربائية',
    'دراجات_ATV_UTV.json' => 'حقول إعلانات الدراجات النارية و ATV و UTV',
    'فانات_وتجاري.json' => 'حقول إعلانات الفانات والمركبات التجارية',
    'مركبات_متضررة.json' => 'حقول إعلانات المركبات المتضررة',
    'كرفانات.json' => 'حقول إعلانات الكرفانات',
    'مركبات_بحرية.json' => 'حقول إعلانات المركبات البحرية',
    'مركبات_جوية.json' => 'حقول إعلانات المركبات الجوية',
];

require __DIR__.'/vehicles_field_translations.php';

if (! is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

$summary = [];

foreach ($files as $csvName => $jsonName) {
    $csvPath = $sourceDir.'/'.$csvName;
    if (! file_exists($csvPath)) {
        $summary[] = ['file' => $jsonName, 'valid' => false, 'error' => "Missing CSV: {$csvName}"];
        continue;
    }

    $rows = parseVehicleCsv($csvPath);
    $fields = array_map('buildVehicleField', $rows);

    $payload = [
        'version' => 1,
        'description' => $descriptions[$jsonName] ?? 'حقول إعلانات المركبات',
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

function parseVehicleCsv(string $path): array
{
    $rows = [];
    $handle = fopen($path, 'r');
    if ($handle === false) {
        throw new RuntimeException("Cannot open: {$path}");
    }

    $lineNum = 0;
    while (($row = fgetcsv($handle)) !== false) {
        $lineNum++;
        if ($lineNum <= 2) {
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
            'options' => splitVehicleOptions($row[5] ?? ''),
        ];
    }
    fclose($handle);

    return $rows;
}

function splitVehicleOptions(string $raw): array
{
    $raw = trim($raw);
    if ($raw === '') {
        return [];
    }

    return array_values(array_filter(array_map('trim', preg_split('/[،,]/u', $raw) ?: [])));
}

function buildVehicleField(array $row): array
{
    global $vehicleLabelTranslations, $vehicleOptionTranslations, $vehicleFieldIds, $vehicleTypeMap;

    $name = $row['name'];
    $csvType = $row['type'];
    $type = $vehicleTypeMap[$csvType] ?? 'text';

    $field = [
        'id' => $vehicleFieldIds[$name] ?? Str::slug($name, '_'),
        'type' => $type,
        'required' => $row['required'],
        'is_active' => true,
        'label' => translateVehicleLabel($name),
    ];

    if (in_array($csvType, ['قائمة متعددة', 'مجموعة اختيارات'], true)) {
        $field['multiple'] = true;
    }

    if ($type === 'select' && $row['options'] !== []) {
        $field['options'] = array_map(
            fn ($opt) => translateVehicleOption($opt),
            $row['options']
        );
    }

    return $field;
}

function translateVehicleLabel(string $ar): array
{
    global $vehicleLabelTranslations;
    $t = $vehicleLabelTranslations[$ar] ?? null;

    return [
        'ar' => $ar,
        'en' => $t['en'] ?? $ar,
        'tr' => $t['tr'] ?? $ar,
    ];
}

function translateVehicleOption(string $ar): array
{
    global $vehicleOptionTranslations;

    if (isset($vehicleOptionTranslations[$ar])) {
        return ['ar' => $ar, 'en' => $vehicleOptionTranslations[$ar]['en'], 'tr' => $vehicleOptionTranslations[$ar]['tr']];
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

    if (preg_match('/^(\d+)\s*-\s*(\d+)/u', $ar, $m)) {
        $en = str_replace([' حصان', ' cc', ' كم', ' طن', ' متر'], [' HP', ' cc', ' km', ' ton', ' m'], $ar);
        $en = preg_replace('/[\x{0600}-\x{06FF}]/u', '', $en);
        $en = trim(preg_replace('/\s+/', ' ', $en));
        if ($en === '') {
            $en = "{$m[1]}–{$m[2]}";
        }

        return ['ar' => $ar, 'en' => $en, 'tr' => $en];
    }

    if (str_contains($ar, 'أكثر من')) {
        $en = str_replace('أكثر من', 'Over', $ar);
        $en = preg_replace('/[\x{0600}-\x{06FF}]/u', '', $en);
        $tr = str_replace('أكثر من', 'Üzeri', $ar);
        $tr = preg_replace('/[\x{0600}-\x{06FF}]/u', '', $tr);

        return ['ar' => $ar, 'en' => trim($en), 'tr' => trim($tr)];
    }

    if (str_contains($ar, 'أقل من')) {
        $en = str_replace('أقل من', 'Under', $ar);
        $en = preg_replace('/[\x{0600}-\x{06FF}]/u', '', $en);
        $tr = str_replace('أقل من', 'Altı', $ar);
        $tr = preg_replace('/[\x{0600}-\x{06FF}]/u', '', $tr);

        return ['ar' => $ar, 'en' => trim($en), 'tr' => trim($tr)];
    }

    return ['ar' => $ar, 'en' => $ar, 'tr' => $ar];
}
