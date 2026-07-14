<?php

/**
 * Convert car rental CSV template → JSON with Arabic filename.
 * Usage: php scripts/convert_car_rental_csv_to_json.php
 */

use Illuminate\Support\Str;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$csvPath = dirname(__DIR__, 2).'/aalenha_Categories/vehicles/aalenha_vehicles_car_rental_templates_ar_v1.csv';
$outputDir = __DIR__.'/../storage/app/custom_fields_templates/vehicles';
$jsonName = 'تأجير_سيارات.json';

require __DIR__.'/vehicles_field_translations.php';

if (! is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

if (! file_exists($csvPath)) {
    fwrite(STDERR, "Missing CSV: {$csvPath}\n");
    exit(1);
}

$rows = parseCarRentalCsv($csvPath);
$fields = array_map('buildVehicleField', $rows);

$payload = [
    'version' => 1,
    'description' => 'حقول إعلانات تأجير السيارات',
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

function parseCarRentalCsv(string $path): array
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
