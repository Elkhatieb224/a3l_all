<?php

/**
 * Apply category/subcategory renames and deletions from م/*.csv
 *
 * Usage:
 *   php scripts/apply_m_csv_category_changes.php --dry-run
 *   php scripts/apply_m_csv_category_changes.php --apply
 */

ini_set('memory_limit', '512M');

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Ad;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Support\Str;

$dryRun = ! in_array('--apply', $argv, true);
echo $dryRun ? "DRY RUN\n\n" : "APPLYING CHANGES\n\n";

$categoryIds = Category::query()
    ->whereIn('name_ar', ['سلع جديدة ومستعملة', 'المركبات'])
    ->pluck('id', 'name_ar')
    ->all();

/** @var array<int, array{id:int,category_id:int,parent_subcategory_id:?int,name_ar:string,slug:string,deleted_at:?string}> */
$nodes = [];
$rows = Subcategory::withTrashed()
    ->whereIn('category_id', array_values($categoryIds))
    ->get(['id', 'category_id', 'parent_subcategory_id', 'name_ar', 'name_en', 'name_tr', 'slug', 'deleted_at']);

foreach ($rows as $row) {
    $nodes[(int) $row->id] = [
        'id' => (int) $row->id,
        'category_id' => (int) $row->category_id,
        'parent_subcategory_id' => $row->parent_subcategory_id !== null ? (int) $row->parent_subcategory_id : null,
        'name_ar' => (string) $row->name_ar,
        'name_en' => (string) ($row->name_en ?? ''),
        'name_tr' => (string) ($row->name_tr ?? ''),
        'slug' => (string) $row->slug,
        'deleted_at' => $row->deleted_at?->toDateTimeString(),
        'model' => $row,
    ];
}

$catNames = Category::query()
    ->whereIn('id', array_values($categoryIds))
    ->pluck('name_ar', 'id')
    ->all();

function buildPath(int $id, array $nodes, array $catNames): string
{
    $parts = [];
    $guard = 0;
    $currentId = $id;
    while (isset($nodes[$currentId]) && $guard < 40) {
        $parts[] = $nodes[$currentId]['name_ar'];
        $parentId = $nodes[$currentId]['parent_subcategory_id'];
        if ($parentId === null) {
            break;
        }
        $currentId = $parentId;
        $guard++;
    }
    $parts = array_reverse($parts);
    $catId = $nodes[$id]['category_id'] ?? null;
    if ($catId && isset($catNames[$catId])) {
        array_unshift($parts, $catNames[$catId]);
    }

    return implode(' > ', $parts);
}

/** @return list<array> */
function findByExactPath(string $path, array $nodes, array $catNames): array
{
    $out = [];
    foreach ($nodes as $node) {
        if (buildPath($node['id'], $nodes, $catNames) === $path) {
            $out[] = $node;
        }
    }

    return $out;
}

/** @return list<array> */
function findByPathFlexible(string $path, array $nodes, array $catNames): array
{
    $exact = findByExactPath($path, $nodes, $catNames);
    if ($exact !== []) {
        return $exact;
    }
    $lower = mb_strtolower($path);
    $out = [];
    foreach ($nodes as $node) {
        if (mb_strtolower(buildPath($node['id'], $nodes, $catNames)) === $lower) {
            $out[] = $node;
        }
    }

    return $out;
}

function uniqueSlug(string $base, int $exceptId): string
{
    $slug = Str::slug($base);
    $candidate = $slug;
    $n = 2;
    while (
        Subcategory::withTrashed()
            ->where('slug', $candidate)
            ->where('id', '!=', $exceptId)
            ->exists()
    ) {
        $candidate = $slug.'-'.$n;
        $n++;
    }

    return $candidate;
}

function renameNode(array $node, string $newNameAr, bool $dryRun): void
{
    $newSlug = uniqueSlug($newNameAr, $node['id']);
    echo "RENAME id={$node['id']}: \"{$node['name_ar']}\" → \"{$newNameAr}\" (slug {$node['slug']} → {$newSlug})\n";
    if ($dryRun) {
        return;
    }
    /** @var Subcategory $model */
    $model = $node['model'];
    $oldName = $model->name_ar;
    $model->name_ar = $newNameAr;
    if ($model->name_en === $oldName) {
        $model->name_en = $newNameAr;
    }
    if ($model->name_tr === $oldName) {
        $model->name_tr = $newNameAr;
    }
    $model->slug = $newSlug;
    $model->save();
}

function deleteNode(array $node, bool $dryRun, string $reason): void
{
    $ads = Ad::where('subcategory_id', $node['id'])->count();
    $deleted = $node['deleted_at'] ? ' [soft-deleted]' : '';
    echo "DELETE id={$node['id']}: \"{$node['name_ar']}\" — {$reason} — ads={$ads}{$deleted}\n";
    if ($dryRun || $node['deleted_at']) {
        return;
    }
    /** @var Subcategory $model */
    $model = $node['model'];
    $suffix = '--deleted-'.$model->id;
    $maxBase = max(1, 255 - strlen($suffix));
    $model->slug = rtrim(substr($model->slug, 0, $maxBase), '-').$suffix;
    $model->saveQuietly();
    $model->delete();
}

$renames = [
    'سلع جديدة ومستعملة > مستلزمات حديقة وبناء' => 'أغراض حديقة وبناء',
    'المركبات > المركبات التجارية > شاحنات وشاحنات خفيفة > Hyundai > H Series > Porter ii' => 'Porter II',
    'المركبات > دراجات نارية > Voge > SR4 Maksi 350' => 'SR4 Maxi 350',
    'المركبات > سيارات > BMW > 3 Series > 323Ti' => '323Ti',
    'المركبات > سيارات > BMW > 3 Series > 325Td' => '325Td',
    'المركبات > سيارات > BMW > 3 Series > 325Ti' => '325Ti',
    'المركبات > سيارات > BMW > 3 Series > 340D xDrive' => '340D xDrive',
    'المركبات > سيارات > BMW > 5 Series > 524Td' => '524Td',
    'المركبات > سيارات > BMW > 5 Series > 550I xDrive' => '550I xDrive',
];

echo "=== RENAMES ===\n";
foreach ($renames as $path => $newName) {
    $matches = findByPathFlexible($path, $nodes, $catNames);
    if ($matches === []) {
        echo "NOT FOUND: {$path}\n";
        continue;
    }
    if (count($matches) > 1) {
        echo "MULTIPLE for {$path}: ".implode(', ', array_column($matches, 'id'))."\n";
        continue;
    }
    $node = $matches[0];
    if ($node['name_ar'] === $newName) {
        echo "SKIP id={$node['id']}: already \"{$newName}\"\n";
        continue;
    }
    renameNode($node, $newName, $dryRun);
    if (! $dryRun) {
        $nodes[$node['id']]['name_ar'] = $newName;
        $nodes[$node['id']]['slug'] = uniqueSlug($newName, $node['id']);
    }
}

$fullDeletes = [
    'سلع جديدة ومستعملة > مقتنيات',
    'سلع جديدة ومستعملة > مجوهرات وأحجار كريمة',
    'سلع جديدة ومستعملة > موسيقى',
    'المركبات > سيارات > Joice',
];

echo "\n=== FULL DELETES ===\n";
foreach ($fullDeletes as $path) {
    $matches = findByPathFlexible($path, $nodes, $catNames);
    if ($matches === [] && str_contains($path, 'Joice')) {
        foreach ($nodes as $node) {
            $p = buildPath($node['id'], $nodes, $catNames);
            if (preg_match('/Joice$/i', $p) && str_contains($p, 'المركبات > سيارات')) {
                $matches[] = $node;
            }
        }
    }
    if ($matches === []) {
        echo "NOT FOUND: {$path}\n";
        continue;
    }
    foreach ($matches as $node) {
        deleteNode($node, $dryRun, 'full delete');
        if (! $dryRun) {
            $nodes[$node['id']]['deleted_at'] = now()->toDateTimeString();
        }
    }
}

$dupPaths = [
    'سلع جديدة ومستعملة > رياضة > ألعاب القوى',
    'سلع جديدة ومستعملة > رياضة > دراجات',
    'سلع جديدة ومستعملة > رياضة > كمال أجسام',
    'سلع جديدة ومستعملة > رياضة > معدات لياقة',
    'سلع جديدة ومستعملة > رياضة > ألعاب داخلية',
    'سلع جديدة ومستعملة > رياضة > فنون قتالية',
    'سلع جديدة ومستعملة > رياضة > بيلاتس ويوغا وجمباز',
    'سلع جديدة ومستعملة > رياضة > رياضات المضرب',
    'سلع جديدة ومستعملة > رياضة > رياضات جماعية',
    'سلع جديدة ومستعملة > رياضة > بيع بالجملة',
];

echo "\n=== DUPLICATE DELETES ===\n";
foreach ($dupPaths as $path) {
    $matches = findByPathFlexible($path, $nodes, $catNames);
    $active = array_values(array_filter($matches, fn ($n) => $n['deleted_at'] === null));
    if (count($active) <= 1) {
        echo (count($active) === 1 ? "OK id={$active[0]['id']}: " : 'NONE: ')."{$path}\n";
        continue;
    }
    usort($active, fn ($a, $b) => $a['id'] <=> $b['id']);
    $keep = array_shift($active);
    echo "KEEP id={$keep['id']} for {$path}\n";
    foreach ($active as $dup) {
        deleteNode($dup, $dryRun, 'duplicate');
        if (! $dryRun) {
            $nodes[$dup['id']]['deleted_at'] = now()->toDateTimeString();
        }
    }
}

echo "\nDone.\n";
