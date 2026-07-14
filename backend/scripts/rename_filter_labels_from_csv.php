<?php

/**
 * Apply filter label renames from تعديل اسم فلتر-Table 1.csv
 * Updates label.ar/en/tr only — field ids unchanged.
 *
 * Usage: php scripts/rename_filter_labels_from_csv.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Category;
use App\Models\Subcategory;

/** @var array<string, array{ar: string, en: string, tr: string}> */
$labelById = [
    // SUV — match السيارات_العام template
    'vehicle_condition' => ['ar' => 'حالة السيارة', 'en' => 'Vehicle Condition', 'tr' => 'Araç Durumu'],
    'color_car' => ['ar' => 'لون السيارة', 'en' => 'Color', 'tr' => 'Renk'],
    'vehicle_source_car' => ['ar' => 'مصدر السيارة', 'en' => 'Vehicle Origin', 'tr' => 'Araç Menşei'],
    // حوادث
    'operating_condition' => ['ar' => 'حالة التشغيل', 'en' => 'Running Condition', 'tr' => 'Çalışma Durumu'],
    'model_year' => ['ar' => 'سنة الصنع', 'en' => 'Model Year', 'tr' => 'Model Yılı'],
    'color_vehicle' => ['ar' => 'لون المركبة', 'en' => 'Color', 'tr' => 'Renk'],
    // خدمات (دراجات)
    'vehicle_condition_bike' => ['ar' => 'حالة الدراجة', 'en' => 'Bike Condition', 'tr' => 'Motosiklet Durumu'],
    'color_bike' => ['ar' => 'لون الدراجة', 'en' => 'Color', 'tr' => 'Renk'],
    // categories — price id kept
    'price_jobs' => ['ar' => 'الراتب / الأجر المتوقع', 'en' => 'Expected Salary', 'tr' => 'Beklenen Ücret'],
    'price_home' => ['ar' => 'الأجر / الراتب المتوقع', 'en' => 'Expected Pay', 'tr' => 'Beklenen Ücret'],
    'price_lessons' => ['ar' => 'الأجر / السعر', 'en' => 'Price / Fee', 'tr' => 'Ücret'],
];

/**
 * @param  array<int, array<string, mixed>>  $fields
 * @param  array<string, array{ar: string, en: string, tr: string}>  $map  fieldId => labels
 * @return array{0: array<int, array<string, mixed>>, 1: list<string>}
 */
function applyLabelMap(array $fields, array $map): array
{
    $log = [];
    foreach ($fields as $i => $field) {
        if (! is_array($field)) {
            continue;
        }
        $id = (string) ($field['id'] ?? '');
        if ($id === '' || ! isset($map[$id])) {
            continue;
        }
        $before = $field['label']['ar'] ?? '';
        $fields[$i]['label'] = [
            'ar' => $map[$id]['ar'],
            'en' => $map[$id]['en'],
            'tr' => $map[$id]['tr'],
        ];
        if ($before !== $map[$id]['ar']) {
            $log[] = "{$id}: {$before} → {$map[$id]['ar']}";
        }
    }

    return [$fields, $log];
}

function updateSubcategory(int $id, array $map, string $name): void
{
    $sub = Subcategory::find($id);
    if (! $sub) {
        echo "Missing subcategory {$id} ({$name})\n";

        return;
    }
    [$fields, $log] = applyLabelMap(is_array($sub->custom_fields) ? $sub->custom_fields : [], $map);
    if ($log === []) {
        echo "{$name} ({$id}): already up to date\n";

        return;
    }
    $sub->custom_fields = $fields;
    $sub->save();
    echo "{$name} ({$id}):\n";
    foreach ($log as $line) {
        echo "  - {$line}\n";
    }
}

function updateCategory(int $id, array $map, string $name): void
{
    $cat = Category::find($id);
    if (! $cat) {
        echo "Missing category {$id} ({$name})\n";

        return;
    }
    [$fields, $log] = applyLabelMap(is_array($cat->custom_fields) ? $cat->custom_fields : [], $map);
    if ($log === []) {
        echo "{$name} ({$id}): already up to date\n";

        return;
    }
    $cat->custom_fields = $fields;
    $cat->save();
    echo "{$name} ({$id}):\n";
    foreach ($log as $line) {
        echo "  - {$line}\n";
    }
}

// المركبات > سيارات دفع رباعي وبيك آب
updateSubcategory(32704, [
    'vehicle_condition' => $labelById['vehicle_condition'],
    'color' => $labelById['color_car'],
    'vehicle_source' => $labelById['vehicle_source_car'],
], 'سيارات دفع رباعي وبيك آب');

// المركبات > مركبات حوادث
updateSubcategory(42602, [
    'operating_condition' => $labelById['operating_condition'],
    'model_year' => $labelById['model_year'],
    'color' => $labelById['color_vehicle'],
], 'مركبات حوادث');

// المركبات > مركبات خدمات
updateSubcategory(42419, [
    'vehicle_condition' => $labelById['vehicle_condition_bike'],
    'model_year' => $labelById['model_year'],
    'color' => $labelById['color_bike'],
], 'مركبات خدمات');

// الوظائف — rename price if present; expected_salary already correct
$jobs = Category::find(3);
if ($jobs) {
    $map = ['price' => $labelById['price_jobs']];
    [$fields, $log] = applyLabelMap(is_array($jobs->custom_fields) ? $jobs->custom_fields : [], $map);
    if ($log !== []) {
        $jobs->custom_fields = $fields;
        $jobs->save();
        echo "الوظائف (3):\n";
        foreach ($log as $line) {
            echo "  - {$line}\n";
        }
    } else {
        echo "الوظائف (3): no price field to rename (expected_salary label already set)\n";
    }
}

// خدمات منزلية
$home = Category::find(11);
if ($home) {
    $map = ['price' => $labelById['price_home']];
    [$fields, $log] = applyLabelMap(is_array($home->custom_fields) ? $home->custom_fields : [], $map);
    if ($log !== []) {
        $home->custom_fields = $fields;
        $home->save();
        echo "خدمات منزلية (11):\n";
        foreach ($log as $line) {
            echo "  - {$line}\n";
        }
    } else {
        echo "خدمات منزلية (11): no price field to rename (expected_pay label already set)\n";
    }
}

// دروس خصوصية
updateCategory(7, [
    'price' => $labelById['price_lessons'],
], 'دروس خصوصية');

echo "Done.\n";
