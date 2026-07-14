<?php

/**
 * Convert real estate CSV templates → JSON with Arabic filenames from ad path.
 * Usage: php scripts/convert_real_estate_csv_to_json.php
 */

use Illuminate\Support\Str;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$baseDir = dirname(__DIR__, 2).'/  عقارات';
$outputDir = __DIR__.'/../storage/app/custom_fields_templates/real_estate';

$csvFiles = [
    'aalenha_real_estate_building_for_rent_templates_ar_v1.csv',
    'aalenha_real_estate_building_for_sale_templates_ar_v1.csv',
    'aalenha_real_estate_commercial_business_transfer_for_sale_templates_ar_v1.csv',
];

require __DIR__.'/real_estate_field_translations.php';

if (! is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

$summary = [];

foreach ($csvFiles as $csvFile) {
    $csvPath = $baseDir.'/'.$csvFile;
    if (! file_exists($csvPath)) {
        $summary[] = ['csv' => $csvFile, 'valid' => false, 'error' => "Missing CSV: {$csvFile}"];
        continue;
    }

    $parsed = parseRealEstateCsv($csvPath);
    $jsonName = pathToJsonFilename($parsed['path']);
    $fields = array_map('buildRealEstateField', $parsed['rows']);

    $payload = [
        'version' => 1,
        'description' => 'حقول إعلانات '.$parsed['path'],
        'fields' => $fields,
    ];

    $outPath = $outputDir.'/'.$jsonName;
    file_put_contents(
        $outPath,
        json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
    );

    $result = App\Support\CustomFieldsJsonImporter::parse(file_get_contents($outPath));
    $summary[] = [
        'csv' => $csvFile,
        'file' => $jsonName,
        'path' => $parsed['path'],
        'fields' => count($fields),
        'valid' => $result['error'] === null,
        'error' => $result['error'],
    ];
}

echo json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n";

function pathToJsonFilename(string $path): string
{
    $segments = preg_split('/\s*>\s*/u', trim($path)) ?: [];
    $segments = array_values(array_filter(array_map('trim', $segments)));

    if ($segments !== [] && $segments[0] === 'عقارات') {
        array_shift($segments);
    }

    $name = implode('_', array_map(
        static fn (string $segment): string => str_replace(' ', '_', $segment),
        $segments
    ));

    return $name.'.json';
}

/**
 * @return array{path: string, rows: list<array{name: string, type: string, required: bool, options: list<string>}>}
 */
function parseRealEstateCsv(string $path): array
{
    $handle = fopen($path, 'r');
    if ($handle === false) {
        throw new RuntimeException("Cannot open: {$path}");
    }

    $headerFound = false;
    $adPath = '';
    $rows = [];

    while (($row = fgetcsv($handle)) !== false) {
        $firstCol = trim($row[0] ?? '');

        if (! $headerFound) {
            if ($firstCol === 'مسار الإعلان') {
                $headerFound = true;
            }
            continue;
        }

        $name = trim($row[1] ?? '');
        if ($name === '') {
            continue;
        }

        if ($adPath === '') {
            $adPath = $firstCol;
        }

        $rows[] = [
            'name' => $name,
            'type' => trim($row[2] ?? ''),
            'required' => trim($row[3] ?? '') === 'نعم',
            'options' => splitRealEstateOptions($row[5] ?? ''),
        ];
    }

    fclose($handle);

    if ($adPath === '') {
        throw new RuntimeException("No ad path found in: {$path}");
    }

    return ['path' => $adPath, 'rows' => $rows];
}

function splitRealEstateOptions(string $raw): array
{
    $raw = trim($raw);
    if ($raw === '') {
        return [];
    }

    return array_values(array_filter(array_map('trim', preg_split('/[،,]/u', $raw) ?: [])));
}

function buildRealEstateField(array $row): array
{
    global $realEstateLabelTranslations, $realEstateOptionTranslations, $realEstateFieldIds, $realEstateTypeMap;

    $name = $row['name'];
    $csvType = $row['type'];
    $type = $realEstateTypeMap[$csvType] ?? 'text';

    $field = [
        'id' => $realEstateFieldIds[$name] ?? Str::slug($name, '_'),
        'type' => $type,
        'required' => $row['required'],
        'is_active' => true,
        'label' => translateRealEstateLabel($name),
    ];

    if (in_array($csvType, ['قائمة متعددة', 'مجموعة اختيار', 'مجموعة اختيارات', 'مجموعة خيارات'], true)) {
        $field['multiple'] = true;
    }

    if ($type === 'select' && $row['options'] !== []) {
        $field['options'] = array_map(
            static fn (string $opt): array => translateRealEstateOption($opt),
            $row['options']
        );
    }

    return $field;
}

function translateRealEstateLabel(string $ar): array
{
    global $realEstateLabelTranslations;
    $t = $realEstateLabelTranslations[$ar] ?? null;

    return [
        'ar' => $ar,
        'en' => $t['en'] ?? $ar,
        'tr' => $t['tr'] ?? $ar,
    ];
}

function translateRealEstateOption(string $ar): array
{
    global $realEstateOptionTranslations;

    if (isset($realEstateOptionTranslations[$ar])) {
        return [
            'ar' => $ar,
            'en' => $realEstateOptionTranslations[$ar]['en'],
            'tr' => $realEstateOptionTranslations[$ar]['tr'],
        ];
    }

    if (preg_match('/^(\d+)$/u', $ar)) {
        return ['ar' => $ar, 'en' => $ar, 'tr' => $ar];
    }

    return ['ar' => $ar, 'en' => $ar, 'tr' => $ar];
}
