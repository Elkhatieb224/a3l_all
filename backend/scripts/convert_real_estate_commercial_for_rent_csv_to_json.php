<?php

/**
 * Convert commercial-for-rent CSV (multi-path blocks) → JSON per ad path.
 * Usage: php scripts/convert_real_estate_commercial_for_rent_csv_to_json.php
 */

use Illuminate\Support\Str;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$baseDir = dirname(__DIR__, 2).'/  عقارات';
$csvPath = $baseDir.'/aalenha_real_estate_commercial_for_rent_templates_ar_v1.csv';
$outputDir = __DIR__.'/../storage/app/custom_fields_templates/real_estate/تجاري_للإيجار';

require __DIR__.'/real_estate_field_translations.php';

if (! is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

if (! file_exists($csvPath)) {
    fwrite(STDERR, "Missing CSV: {$csvPath}\n");
    exit(1);
}

$blocks = parseMultiPathRealEstateCsv($csvPath);
$summary = [];

foreach ($blocks as $block) {
    $fields = array_map('buildRealEstateField', $block['rows']);

    foreach ($block['paths'] as $adPath) {
        $jsonName = pathToJsonFilename($adPath);

        $payload = [
            'version' => 1,
            'description' => 'حقول إعلانات '.$adPath,
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
            'path' => $adPath,
            'fields' => count($fields),
            'valid' => $result['error'] === null,
            'error' => $result['error'],
        ];
    }
}

echo json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n";
echo 'Total: '.count($summary).' files, '.
    count(array_filter($summary, static fn (array $r): bool => $r['valid'])).' valid'."\n";

/**
 * @return list<array{paths: list<string>, rows: list<array{name: string, type: string, required: bool, options: list<string>}>}>
 */
function parseMultiPathRealEstateCsv(string $path): array
{
    $handle = fopen($path, 'r');
    if ($handle === false) {
        throw new RuntimeException("Cannot open: {$path}");
    }

    $headerFound = false;
    $blocks = [];
    $currentPaths = [];
    $currentRows = [];

    while (($row = fgetcsv($handle)) !== false) {
        $firstCol = trim($row[0] ?? '');

        if (! $headerFound) {
            if ($firstCol === 'مسار الإعلان') {
                $headerFound = true;
            }
            continue;
        }

        $name = trim($row[1] ?? '');

        if ($firstCol !== '' && str_contains($firstCol, 'عقارات')) {
            if ($currentPaths !== []) {
                $blocks[] = ['paths' => $currentPaths, 'rows' => $currentRows];
            }
            $currentPaths = splitAdPaths($firstCol);
            $currentRows = [];
        }

        if ($name === '') {
            continue;
        }

        $currentRows[] = [
            'name' => $name,
            'type' => trim($row[2] ?? ''),
            'required' => trim($row[3] ?? '') === 'نعم',
            'options' => splitRealEstateOptions($row[5] ?? ''),
        ];
    }

    fclose($handle);

    if ($currentPaths !== []) {
        $blocks[] = ['paths' => $currentPaths, 'rows' => $currentRows];
    }

    if ($blocks === []) {
        throw new RuntimeException("No path blocks found in: {$path}");
    }

    return $blocks;
}

/** @return list<string> */
function splitAdPaths(string $raw): array
{
    $lines = preg_split('/\R/u', trim($raw)) ?: [];

    return array_values(array_filter(array_map('trim', $lines)));
}

function pathToJsonFilename(string $path): string
{
    $segments = preg_split('/\s*>\s*/u', trim($path)) ?: [];
    $segments = array_values(array_filter(array_map('trim', $segments)));

    if ($segments !== [] && $segments[0] === 'عقارات') {
        array_shift($segments);
    }

    $name = implode('_', array_map(
        static function (string $segment): string {
            $segment = preg_replace('/\s*\/\s*/u', '_', $segment) ?? $segment;

            return str_replace(' ', '_', $segment);
        },
        $segments
    ));

    return $name.'.json';
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

    if (preg_match('/^(\d+)$/u', $ar) || preg_match('/^(\d+)-(\d+)$/u', $ar)) {
        return ['ar' => $ar, 'en' => $ar, 'tr' => $ar];
    }

    return ['ar' => $ar, 'en' => $ar, 'tr' => $ar];
}
