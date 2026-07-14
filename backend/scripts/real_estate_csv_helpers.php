<?php

use Illuminate\Support\Str;

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

/** @return list<string> */
function splitAdPaths(string $raw): array
{
    $lines = preg_split('/\R/u', trim($raw)) ?: [];

    return array_values(array_filter(array_map('trim', $lines)));
}

function splitRealEstateOptions(string $raw): array
{
    $raw = trim($raw);
    if ($raw === '') {
        return [];
    }

    return array_values(array_filter(array_map('trim', preg_split('/[،,]/u', $raw) ?: [])));
}

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

        $currentRows[] = parseRealEstateFieldRow($row);
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

/**
 * @return array{path: string, rows: list<array{name: string, type: string, required: bool, options: list<string>}>}
 */
function parseSinglePathRealEstateCsv(string $path): array
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

        if ($adPath === '' && $firstCol !== '' && str_contains($firstCol, 'عقارات')) {
            $adPath = $firstCol;
        }

        $rows[] = parseRealEstateFieldRow($row);
    }

    fclose($handle);

    if ($adPath === '') {
        throw new RuntimeException("No ad path found in: {$path}");
    }

    return ['path' => $adPath, 'rows' => $rows];
}

/** @return array{name: string, type: string, required: bool, options: list<string>} */
function parseRealEstateFieldRow(array $row): array
{
    return [
        'name' => trim($row[1] ?? ''),
        'type' => trim($row[2] ?? ''),
        'required' => trim($row[3] ?? '') === 'نعم',
        'options' => splitRealEstateOptions($row[5] ?? ''),
    ];
}

function buildRealEstateField(array $row): array
{
    global $realEstateLabelTranslations, $realEstateOptionTranslations, $realEstateFieldIds, $realEstateTypeMap;

    $name = $row['name'];
    $csvType = $row['type'];
    $type = $realEstateTypeMap[$csvType] ?? 'text';

    $slug = Str::slug($name, '_');
    $fieldId = $realEstateFieldIds[$name] ?? ($slug !== '' ? $slug : 'field_'.substr(md5($name), 0, 8));

    $field = [
        'id' => $fieldId,
        'type' => $type,
        'required' => $row['required'],
        'is_active' => true,
        'label' => translateRealEstateLabel($name),
    ];

    if (in_array($csvType, [
        'قائمة متعددة',
        'مجموعة اختيار',
        'مجموعة اختيارات',
        'مجموعة خيارات',
        'قائمة متعددة الاختيار',
        'مجموعة اختيار متعدد',
    ], true)) {
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

    if (preg_match('/^(\d+)$/u', $ar) || preg_match('/^(\d+)[-+](\d+)$/u', $ar) || preg_match('/^(\d+)\+$/u', $ar)) {
        return ['ar' => $ar, 'en' => $ar, 'tr' => $ar];
    }

    return ['ar' => $ar, 'en' => $ar, 'tr' => $ar];
}

/**
 * @return list<array{file: string, path: string, fields: int, valid: bool, error: ?string}>
 */
function writeRealEstateJsonOutputs(string $outputDir, array $items): array
{
    if (! is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }

    $summary = [];

    foreach ($items as $item) {
        $adPath = $item['path'];
        $fields = $item['fields'];
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

    return $summary;
}
