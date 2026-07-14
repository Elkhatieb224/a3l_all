<?php

/**
 * Convert vehicle parts CSV template → JSON with Arabic filename.
 * Usage: php scripts/convert_vehicle_parts_csv_to_json.php
 */

use Illuminate\Support\Str;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$csvPath = dirname(__DIR__, 2).'/aalenha_Categories/vehicle_parts/aalenha_vehicle_parts_templates_ar_v1.csv';
$outputDir = __DIR__.'/../storage/app/custom_fields_templates/vehicle_parts';
$jsonName = 'قطع_غيار_السيارات.json';

require __DIR__.'/vehicle_parts_field_translations.php';

if (! is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

if (! file_exists($csvPath)) {
    fwrite(STDERR, "Missing CSV: {$csvPath}\n");
    exit(1);
}

$rows = parseVehiclePartsCsv($csvPath);
$fields = array_map('buildVehiclePartsField', $rows);

$payload = [
    'version' => 1,
    'description' => 'حقول إعلانات قطع غيار السيارات (معدات السيارات، الدراجات النارية، المركبات البحرية)',
    'fields' => $fields,
];

$outPath = $outputDir.'/'.$jsonName;
file_put_contents(
    $outPath,
    json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
);

$result = App\Support\CustomFieldsJsonImporter::parse(file_get_contents($outPath));

echo json_encode([
    'file' => $jsonName,
    'path' => $outPath,
    'fields' => count($fields),
    'valid' => $result['error'] === null,
    'error' => $result['error'],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n";

function parseVehiclePartsCsv(string $path): array
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
            'options' => splitVehiclePartsOptions($row[5] ?? ''),
        ];
    }
    fclose($handle);

    return $rows;
}

function splitVehiclePartsOptions(string $raw): array
{
    $raw = trim($raw);
    if ($raw === '') {
        return [];
    }

    return array_values(array_filter(array_map('trim', preg_split('/[،,]/u', $raw) ?: [])));
}

function buildVehiclePartsField(array $row): array
{
    global $vehiclePartsLabelTranslations, $vehiclePartsOptionTranslations, $vehiclePartsFieldIds, $vehiclePartsTypeMap;

    $name = $row['name'];
    $csvType = $row['type'];
    $type = $vehiclePartsTypeMap[$csvType] ?? 'text';

    $field = [
        'id' => $vehiclePartsFieldIds[$name] ?? Str::slug($name, '_'),
        'type' => $type,
        'required' => $row['required'],
        'is_active' => true,
        'label' => translateVehiclePartsLabel($name),
    ];

    if (in_array($csvType, ['قائمة متعددة', 'مجموعة اختيارات'], true)) {
        $field['multiple'] = true;
    }

    if ($type === 'select' && $row['options'] !== []) {
        $field['options'] = array_map(
            fn ($opt) => translateVehiclePartsOption($opt),
            $row['options']
        );
    }

    return $field;
}

function translateVehiclePartsLabel(string $ar): array
{
    global $vehiclePartsLabelTranslations;
    $t = $vehiclePartsLabelTranslations[$ar] ?? null;

    return [
        'ar' => $ar,
        'en' => $t['en'] ?? $ar,
        'tr' => $t['tr'] ?? $ar,
    ];
}

function translateVehiclePartsOption(string $ar): array
{
    global $vehiclePartsOptionTranslations;

    if (isset($vehiclePartsOptionTranslations[$ar])) {
        return ['ar' => $ar, 'en' => $vehiclePartsOptionTranslations[$ar]['en'], 'tr' => $vehiclePartsOptionTranslations[$ar]['tr']];
    }

    if (preg_match('/^(\d+)$/u', $ar)) {
        return ['ar' => $ar, 'en' => $ar, 'tr' => $ar];
    }

    return ['ar' => $ar, 'en' => $ar, 'tr' => $ar];
}
