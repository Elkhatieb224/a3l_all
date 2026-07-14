<?php

/**
 * Convert animals/pets CSV templates → JSON with Arabic filenames.
 * Usage: php scripts/convert_animals_csv_to_json.php
 */

use Illuminate\Support\Str;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$sourceDir = dirname(__DIR__, 2).'/aalenha_Categories/animals_pets/aalenha_animals_pets_templates_ar_v1';
$outputDir = __DIR__.'/../storage/app/custom_fields_templates/animals_pets';

$files = [
    'الحيوانات-Table 1.csv' => 'الحيوانات.json',
    'الإكسسوارات والمعدات-Table 1.csv' => 'الإكسسوارات_والمعدات.json',
];

$descriptions = [
    'الحيوانات.json' => 'حقول إعلانات الحيوانات الأليفة والمواشي',
    'الإكسسوارات_والمعدات.json' => 'حقول إعلانات الإكسسوارات والمعدات والأعلاف والطعام',
];

require __DIR__.'/animals_field_translations.php';

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

    $rows = parseAnimalsCsv($csvPath);
    $fields = array_map('buildAnimalsField', $rows);

    $payload = [
        'version' => 1,
        'description' => $descriptions[$jsonName] ?? 'حقول إعلانات الحيوانات',
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

function parseAnimalsCsv(string $path): array
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
            'options' => splitAnimalsOptions($row[5] ?? ''),
        ];
    }
    fclose($handle);

    return $rows;
}

function splitAnimalsOptions(string $raw): array
{
    $raw = trim($raw);
    if ($raw === '') {
        return [];
    }

    return array_values(array_filter(array_map('trim', preg_split('/[،,]/u', $raw) ?: [])));
}

function buildAnimalsField(array $row): array
{
    global $animalsLabelTranslations, $animalsOptionTranslations, $animalsFieldIds, $animalsTypeMap;

    $name = $row['name'];
    $csvType = $row['type'];
    $type = $animalsTypeMap[$csvType] ?? 'text';

    $field = [
        'id' => $animalsFieldIds[$name] ?? Str::slug($name, '_'),
        'type' => $type,
        'required' => $row['required'],
        'is_active' => true,
        'label' => translateAnimalsLabel($name),
    ];

    if (in_array($csvType, ['قائمة متعددة', 'مجموعة اختيارات'], true)) {
        $field['multiple'] = true;
    }

    if ($type === 'select' && $row['options'] !== []) {
        $field['options'] = array_map(
            fn ($opt) => translateAnimalsOption($opt),
            $row['options']
        );
    }

    return $field;
}

function translateAnimalsLabel(string $ar): array
{
    global $animalsLabelTranslations;
    $t = $animalsLabelTranslations[$ar] ?? null;

    return [
        'ar' => $ar,
        'en' => $t['en'] ?? $ar,
        'tr' => $t['tr'] ?? $ar,
    ];
}

function translateAnimalsOption(string $ar): array
{
    global $animalsOptionTranslations;

    if (isset($animalsOptionTranslations[$ar])) {
        return ['ar' => $ar, 'en' => $animalsOptionTranslations[$ar]['en'], 'tr' => $animalsOptionTranslations[$ar]['tr']];
    }

    if (preg_match('/^(\d+)$/u', $ar)) {
        return ['ar' => $ar, 'en' => $ar, 'tr' => $ar];
    }

    return ['ar' => $ar, 'en' => $ar, 'tr' => $ar];
}
