<?php

/**
 * Convert real estate CSV templates → JSON (single-path or multi-path blocks).
 * Usage: php scripts/convert_real_estate_batch.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

require __DIR__.'/real_estate_field_translations.php';
require __DIR__.'/real_estate_csv_helpers.php';

$baseDir = dirname(__DIR__, 2).'/  عقارات';
$rootOutputDir = __DIR__.'/../storage/app/custom_fields_templates/real_estate';

$templates = [
    [
        'csv' => 'aalenha_real_estate_commercial_for_sale_templates_ar_v1.csv',
        'mode' => 'multi',
        'subdir' => 'تجاري_للبيع',
    ],
    [
        'csv' => 'aalenha_real_estate_land_construction_share_templates_ar_v1.csv',
        'mode' => 'single',
        'subdir' => null,
    ],
    [
        'csv' => 'aalenha_real_estate_land_for_rent_templates_ar_v1.csv',
        'mode' => 'single',
        'subdir' => null,
    ],
    [
        'csv' => 'aalenha_real_estate_land_for_sale_templates_ar_v1.csv',
        'mode' => 'single',
        'subdir' => null,
    ],
    [
        'csv' => 'aalenha_real_estate_residential_daily_rental_single_template_ar_v1.csv',
        'mode' => 'single',
        'subdir' => null,
    ],
    [
        'csv' => 'aalenha_real_estate_residential_for_rent_templates_ar_v1.csv',
        'mode' => 'multi',
        'subdir' => 'سكني_للإيجار',
    ],
    [
        'csv' => 'aalenha_real_estate_residential_for_sale_templates_ar_v1.csv',
        'mode' => 'multi',
        'subdir' => 'سكني_للبيع',
    ],
    [
        'csv' => 'aalenha_real_estate_timeshare_for_sale_templates_ar_v1.csv',
        'mode' => 'single',
        'subdir' => null,
    ],
    [
        'csv' => 'aalenha_real_estate_touristic_facility_for_rent_templates_ar_v1.csv',
        'mode' => 'single',
        'subdir' => null,
    ],
];

$allSummary = [];

foreach ($templates as $template) {
    $csvPath = $baseDir.'/'.$template['csv'];
    $outputDir = $template['subdir'] !== null
        ? $rootOutputDir.'/'.$template['subdir']
        : $rootOutputDir;

    if (! file_exists($csvPath)) {
        $allSummary[] = [
            'csv' => $template['csv'],
            'valid' => false,
            'error' => 'Missing CSV',
        ];
        continue;
    }

    $items = [];

    if ($template['mode'] === 'multi') {
        $blocks = parseMultiPathRealEstateCsv($csvPath);
        foreach ($blocks as $block) {
            $fields = array_map('buildRealEstateField', $block['rows']);
            foreach ($block['paths'] as $adPath) {
                $items[] = ['path' => $adPath, 'fields' => $fields];
            }
        }
    } else {
        $parsed = parseSinglePathRealEstateCsv($csvPath);
        $fields = array_map('buildRealEstateField', $parsed['rows']);
        $items[] = ['path' => $parsed['path'], 'fields' => $fields];
    }

    $summary = writeRealEstateJsonOutputs($outputDir, $items);

    foreach ($summary as $row) {
        $allSummary[] = array_merge(['csv' => $template['csv'], 'subdir' => $template['subdir']], $row);
    }
}

$validCount = count(array_filter($allSummary, static fn (array $r): bool => ($r['valid'] ?? false) === true));
echo json_encode($allSummary, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n";
echo "Total: {$validCount}/".count($allSummary)." valid\n";
