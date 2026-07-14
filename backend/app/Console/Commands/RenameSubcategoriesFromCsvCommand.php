<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RenameSubcategoriesFromCsvCommand extends Command
{
    protected $signature = 'subcategories:rename-from-csv
                            {file? : Path to the CSV file}
                            {--dry-run : Preview changes without writing to the database}
                            {--audit : Verify each CSV row against DB and write an audit report}
                            {--fix-missing : With --audit, apply updates for rows that do not match yet}
                            {--limit= : Process only the first N data rows}
                            {--report= : Write a detailed report CSV to this path}';

    protected $description = 'Bulk-rename category/subcategory name_ar values from a CSV path file';

    private const ACTION_RENAME = 'تعديل الاسم';

    /** @var array<int, array{id: int, category_id: int, parent_subcategory_id: ?int, name_ar: string, name_en: string, name_tr: string}> */
    private array $subcategoryRows = [];

    /** @var array<int, list<int>> */
    private array $childrenByParent = [];

    /** @var array<int, list<int>> */
    private array $rootSubcategoryIdsByCategory = [];

    /** @var array<int, array{id: int, name_ar: string, name_en: string, name_tr: string}> */
    private array $categoryRows = [];

    public function handle(): int
    {
        $file = $this->argument('file') ?? base_path('تعديل الاسم-Table 1.csv');

        if (! is_readable($file)) {
            $this->error("CSV file not readable: {$file}");

            return self::FAILURE;
        }

        $this->loadCatalog();

        $dryRun = (bool) $this->option('dry-run') || ((bool) $this->option('audit') && ! (bool) $this->option('fix-missing'));
        $audit = (bool) $this->option('audit');
        $limit = $this->option('limit') !== null ? max(1, (int) $this->option('limit')) : null;

        $stats = [
            'rows' => 0,
            'updated' => 0,
            'skipped_same' => 0,
            'skipped_action' => 0,
            'not_found' => 0,
            'name_mismatch' => 0,
            'ambiguous' => 0,
            'errors' => 0,
        ];

        $reportHandle = null;
        $reportPath = $this->option('report');
        if (is_string($reportPath) && $reportPath !== '') {
            $dir = dirname($reportPath);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $reportHandle = fopen($reportPath, 'wb');
            if ($reportHandle !== false) {
                fputcsv($reportHandle, ['line', 'status', 'message', 'path', 'current', 'new', 'entity_type', 'entity_id', 'old_name_ar']);
            }
        }

        $handle = fopen($file, 'rb');
        if ($handle === false) {
            $this->error('Failed to open CSV file.');

            return self::FAILURE;
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            $this->error('CSV file is empty.');

            return self::FAILURE;
        }

        $header = $this->stripBom($header);
        $columnMap = $this->mapColumns($header);

        if (! isset($columnMap['path'], $columnMap['current'], $columnMap['new'])) {
            fclose($handle);
            $this->error('CSV must include columns: المسار الحالي, الاسم الحالي, الاسم الصحيح الجديد');

            return self::FAILURE;
        }

        $processed = 0;

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                if ($row === [null] || $row === false || $this->isEmptyRow($row)) {
                    continue;
                }

                $stats['rows']++;
                $lineNumber = $stats['rows'] + 1;

                $action = isset($columnMap['action'])
                    ? trim((string) ($row[$columnMap['action']] ?? ''))
                    : self::ACTION_RENAME;

                if ($action !== '' && $action !== self::ACTION_RENAME) {
                    $stats['skipped_action']++;
                    continue;
                }

                $path = trim((string) ($row[$columnMap['path']] ?? ''));
                $currentName = trim((string) ($row[$columnMap['current']] ?? ''));
                $newName = trim((string) ($row[$columnMap['new']] ?? ''));

                if ($path === '' || $currentName === '' || $newName === '') {
                    $stats['errors']++;
                    $this->writeReportRow($reportHandle, $lineNumber, 'error', 'Missing path, current name, or new name', $path, $currentName, $newName, null);
                    continue;
                }

                $result = $this->processRow($path, $currentName, $newName, $dryRun);

                $this->writeReportRow(
                    $reportHandle,
                    $lineNumber,
                    $result['status'],
                    $result['message'],
                    $path,
                    $currentName,
                    $newName,
                    $result['entity'] ?? null,
                );

                $stats[$result['stat']]++;

                if ($result['status'] === 'updated' || $result['status'] === 'would_update') {
                    $processed++;
                }

                if ($limit !== null && $processed >= $limit) {
                    break;
                }
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
                if ($stats['updated'] > 0) {
                    $this->bumpApiCacheVersions();
                }
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            throw $e;
        }

        fclose($handle);

        if ($reportHandle !== false && $reportHandle !== null) {
            fclose($reportHandle);
            $this->info("Report written to: {$reportPath}");
        }

        $this->newLine();
        if ($audit) {
            $this->info($dryRun ? 'AUDIT — no database changes were saved.' : 'AUDIT — missing rows were fixed.');
        } else {
            $this->info($dryRun ? 'DRY RUN — no database changes were saved.' : 'Changes committed to the database.');
        }
        $this->table(
            ['Metric', 'Count'],
            collect($stats)->map(fn ($count, $key) => [$key, $count])->values()->all()
        );

        return ($stats['not_found'] + $stats['name_mismatch'] + $stats['ambiguous'] + $stats['errors']) > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    private function loadCatalog(): void
    {
        $this->categoryRows = [];
        foreach (DB::table('categories')->select(['id', 'name_ar', 'name_en', 'name_tr'])->cursor() as $row) {
            $id = (int) $row->id;
            $this->categoryRows[$id] = [
                'id' => $id,
                'name_ar' => (string) $row->name_ar,
                'name_en' => (string) $row->name_en,
                'name_tr' => (string) $row->name_tr,
            ];
        }

        $this->subcategoryRows = [];
        foreach (DB::table('subcategories')->select(['id', 'category_id', 'parent_subcategory_id', 'name_ar', 'name_en', 'name_tr'])->cursor() as $row) {
            $id = (int) $row->id;
            $this->subcategoryRows[$id] = [
                'id' => $id,
                'category_id' => (int) $row->category_id,
                'parent_subcategory_id' => $row->parent_subcategory_id !== null ? (int) $row->parent_subcategory_id : null,
                'name_ar' => (string) $row->name_ar,
                'name_en' => (string) $row->name_en,
                'name_tr' => (string) $row->name_tr,
            ];
        }

        $this->childrenByParent = [];
        $this->rootSubcategoryIdsByCategory = [];

        foreach ($this->subcategoryRows as $id => $row) {
            if ($row['parent_subcategory_id'] === null) {
                $this->rootSubcategoryIdsByCategory[$row['category_id']][] = $id;
            } else {
                $this->childrenByParent[$row['parent_subcategory_id']][] = $id;
            }
        }
    }

    /**
     * @return array{status: string, stat: string, message: string, entity?: array{type: string, id: int, old: string, new: string}}
     */
    private function processRow(string $path, string $currentName, string $newName, bool $dryRun): array
    {
        $segments = $this->splitPath($path);

        if ($segments === []) {
            return [
                'status' => 'error',
                'stat' => 'errors',
                'message' => 'Empty path',
            ];
        }

        if ($this->namesMatch($segments[count($segments) - 1], $currentName) === false) {
            return [
                'status' => 'name_mismatch',
                'stat' => 'name_mismatch',
                'message' => 'Path leaf does not match current name column',
            ];
        }

        if (count($segments) === 1) {
            return $this->renameCategoryByPath($segments[0], $currentName, $newName, $dryRun);
        }

        $category = $this->findCategory($segments[0], array_slice($segments, 1), $currentName, $newName);
        if ($category === null) {
            return [
                'status' => 'not_found',
                'stat' => 'not_found',
                'message' => 'Category not found: '.$segments[0],
            ];
        }

        $parentId = null;
        $subSegments = array_slice($segments, 1);

        foreach ($subSegments as $index => $segment) {
            $isLeaf = $index === count($subSegments) - 1;
            $matches = $this->findSubcategoryChildren($category['id'], $parentId, $segment);

            if (count($matches) > 1) {
                $matches = $this->disambiguateSubcategoryMatches(
                    $matches,
                    $category['id'],
                    $parentId,
                    $isLeaf,
                    $currentName,
                    array_slice($subSegments, $index + 1),
                    $newName,
                );
            }

            if ($matches === []) {
                if ($isLeaf) {
                    $matches = $this->findSubcategoryChildrenByExactName($category['id'], $parentId, $newName);
                }

                if ($matches === []) {
                    return [
                        'status' => 'not_found',
                        'stat' => 'not_found',
                        'message' => 'Subcategory not found at segment: '.$segment,
                    ];
                }
            }

            if (count($matches) > 1 && $isLeaf) {
                return $this->applySubcategoryRenames($matches, $currentName, $newName, $dryRun);
            }

            if (count($matches) > 1) {
                return [
                    'status' => 'ambiguous',
                    'stat' => 'ambiguous',
                    'message' => 'Multiple subcategories match segment: '.$segment,
                ];
            }

            $subId = $matches[0];
            $sub = $this->subcategoryRows[$subId];

            if ($isLeaf) {
                if (! $this->namesMatch($sub['name_ar'], $currentName)) {
                    if ($this->namesMatchExact($sub['name_ar'], $newName)) {
                        return [
                            'status' => 'skipped_same',
                            'stat' => 'skipped_same',
                            'message' => 'Already has target name_ar',
                            'entity' => [
                                'type' => 'subcategory',
                                'id' => $subId,
                                'old' => $sub['name_ar'],
                                'new' => $newName,
                            ],
                        ];
                    }

                    return [
                        'status' => 'name_mismatch',
                        'stat' => 'name_mismatch',
                        'message' => 'DB name_ar does not match current name (id '.$subId.')',
                    ];
                }

                if ($this->namesMatchExact($sub['name_ar'], $newName)) {
                    return [
                        'status' => 'skipped_same',
                        'stat' => 'skipped_same',
                        'message' => 'Already has target name_ar',
                    ];
                }

                if (! $dryRun) {
                    DB::table('subcategories')->where('id', $subId)->update([
                        'name_ar' => $newName,
                        'updated_at' => now(),
                    ]);
                    $this->subcategoryRows[$subId]['name_ar'] = $newName;
                }

                return [
                    'status' => $dryRun ? 'would_update' : 'updated',
                    'stat' => 'updated',
                    'message' => $dryRun ? 'Would update subcategory' : 'Updated subcategory',
                    'entity' => [
                        'type' => 'subcategory',
                        'id' => $subId,
                        'old' => $sub['name_ar'],
                        'new' => $newName,
                    ],
                ];
            }

            $parentId = $subId;
        }

        return [
            'status' => 'error',
            'stat' => 'errors',
            'message' => 'Unresolved path',
        ];
    }

    /**
     * @return array{status: string, stat: string, message: string, entity?: array{type: string, id: int, old: string, new: string}}
     */
    private function renameCategoryByPath(string $categorySegment, string $currentName, string $newName, bool $dryRun): array
    {
        $category = $this->findCategory($categorySegment, [], $currentName, $newName);
        if ($category === null) {
            return [
                'status' => 'not_found',
                'stat' => 'not_found',
                'message' => 'Category not found: '.$categorySegment,
            ];
        }

        if (! $this->namesMatch($category['name_ar'], $currentName)) {
            return [
                'status' => 'name_mismatch',
                'stat' => 'name_mismatch',
                'message' => 'DB category name_ar does not match current name (id '.$category['id'].')',
            ];
        }

        if ($this->namesMatchExact($category['name_ar'], $newName)) {
            return [
                'status' => 'skipped_same',
                'stat' => 'skipped_same',
                'message' => 'Category already has target name_ar',
            ];
        }

        if (! $dryRun) {
            DB::table('categories')->where('id', $category['id'])->update([
                'name_ar' => $newName,
                'updated_at' => now(),
            ]);
            $this->categoryRows[$category['id']]['name_ar'] = $newName;
        }

        return [
            'status' => $dryRun ? 'would_update' : 'updated',
            'stat' => 'updated',
            'message' => $dryRun ? 'Would update category' : 'Updated category',
            'entity' => [
                'type' => 'category',
                'id' => $category['id'],
                'old' => $category['name_ar'],
                'new' => $newName,
            ],
        ];
    }

    /**
     * @param  list<int>  $ids
     * @return array{status: string, stat: string, message: string, entity?: array{type: string, id: int, old: string, new: string}}
     */
    private function applySubcategoryRenames(array $ids, string $currentName, string $newName, bool $dryRun): array
    {
        $targets = array_values(array_filter(
            $ids,
            fn (int $id) => $this->namesMatch($this->subcategoryRows[$id]['name_ar'], $currentName)
        ));

        if ($targets === []) {
            return [
                'status' => 'name_mismatch',
                'stat' => 'name_mismatch',
                'message' => 'No matching duplicates share the current name',
            ];
        }

        $needsUpdate = array_values(array_filter(
            $targets,
            fn (int $id) => ! $this->namesMatchExact($this->subcategoryRows[$id]['name_ar'], $newName)
        ));

        if ($needsUpdate === []) {
            return [
                'status' => 'skipped_same',
                'stat' => 'skipped_same',
                'message' => 'All duplicates already have target name_ar',
            ];
        }

        $firstId = $needsUpdate[0];
        $oldName = $this->subcategoryRows[$firstId]['name_ar'];

        if (! $dryRun) {
            DB::table('subcategories')->whereIn('id', $needsUpdate)->update([
                'name_ar' => $newName,
                'updated_at' => now(),
            ]);
            foreach ($needsUpdate as $id) {
                $this->subcategoryRows[$id]['name_ar'] = $newName;
            }
        }

        return [
            'status' => $dryRun ? 'would_update' : 'updated',
            'stat' => 'updated',
            'message' => ($dryRun ? 'Would update' : 'Updated').' '.count($needsUpdate).' duplicate subcategories',
            'entity' => [
                'type' => 'subcategory',
                'id' => $firstId,
                'old' => $oldName,
                'new' => $newName,
            ],
        ];
    }

    /**
     * @param  list<int>  $matches
     * @param  list<string>  $remainingSegments
     * @return list<int>
     */
    private function disambiguateSubcategoryMatches(
        array $matches,
        int $categoryId,
        ?int $parentId,
        bool $isLeaf,
        string $currentName,
        array $remainingSegments,
        string $newName,
    ): array {
        if ($isLeaf) {
            $filtered = array_values(array_filter(
                $matches,
                fn (int $id) => $this->namesMatch($this->subcategoryRows[$id]['name_ar'], $currentName)
            ));

            return $filtered !== [] ? $filtered : $matches;
        }

        if ($remainingSegments === []) {
            return $matches;
        }

        $filtered = [];
        foreach ($matches as $id) {
            if ($this->canResolveFromParent($categoryId, $id, $remainingSegments, $currentName, $newName)) {
                $filtered[] = $id;
            }
        }

        return $filtered !== [] ? $filtered : $matches;
    }

    /**
     * @param  list<string>  $segments
     */
    private function canResolveFromParent(int $categoryId, int $parentId, array $segments, string $currentName, string $newName): bool
    {
        $currentParent = $parentId;

        foreach ($segments as $index => $segment) {
            $isLeaf = $index === count($segments) - 1;
            $children = $this->findSubcategoryChildren($categoryId, $currentParent, $segment);

            if (count($children) > 1) {
                $children = $this->disambiguateSubcategoryMatches(
                    $children,
                    $categoryId,
                    $currentParent,
                    $isLeaf,
                    $currentName,
                    array_slice($segments, $index + 1),
                    $newName,
                );
            }

            if (count($children) !== 1) {
                if ($isLeaf) {
                    $children = array_values(array_unique(array_merge(
                        $this->findSubcategoryChildrenByExactName($categoryId, $currentParent, $newName),
                        $this->findSubcategoryChildrenByCurrentName($categoryId, $currentParent, $currentName),
                    )));
                }

                if (count($children) !== 1) {
                    return false;
                }
            }

            if ($isLeaf) {
                return $this->namesMatchExact($this->subcategoryRows[$children[0]]['name_ar'], $newName)
                    || $this->namesMatch($this->subcategoryRows[$children[0]]['name_ar'], $currentName);
            }

            $currentParent = $children[0];
        }

        return false;
    }

    /**
     * @return list<int>
     */
    private function findSubcategoryByCategoryAndCurrentName(int $categoryId, string $currentName): array
    {
        $matches = [];
        foreach ($this->subcategoryRows as $id => $row) {
            if ($row['category_id'] !== $categoryId) {
                continue;
            }
            if ($this->namesMatch($row['name_ar'], $currentName)) {
                $matches[] = $id;
            }
        }

        return $matches;
    }

    /**
     * @return list<int>
     */
    private function findSubcategoryChildrenByExactName(int $categoryId, ?int $parentId, string $name): array
    {
        $candidateIds = $parentId === null
            ? ($this->rootSubcategoryIdsByCategory[$categoryId] ?? [])
            : ($this->childrenByParent[$parentId] ?? []);

        $matches = [];
        foreach ($candidateIds as $id) {
            $row = $this->subcategoryRows[$id];
            if ($row['category_id'] !== $categoryId) {
                continue;
            }
            if ($this->namesMatchExact($row['name_ar'], $name)) {
                $matches[] = $id;
            }
        }

        return $matches;
    }

    /**
     * @return list<int>
     */
    private function findSubcategoryChildrenByCurrentName(int $categoryId, ?int $parentId, string $currentName): array
    {
        $candidateIds = $parentId === null
            ? ($this->rootSubcategoryIdsByCategory[$categoryId] ?? [])
            : ($this->childrenByParent[$parentId] ?? []);

        $matches = [];
        foreach ($candidateIds as $id) {
            $row = $this->subcategoryRows[$id];
            if ($row['category_id'] !== $categoryId) {
                continue;
            }
            if ($this->namesMatch($row['name_ar'], $currentName)) {
                $matches[] = $id;
            }
        }

        return $matches;
    }

    /**
     * @return list<int>
     */
    private function findSubcategoryChildren(int $categoryId, ?int $parentId, string $segment): array
    {
        $candidateIds = $parentId === null
            ? ($this->rootSubcategoryIdsByCategory[$categoryId] ?? [])
            : ($this->childrenByParent[$parentId] ?? []);

        $matches = [];
        foreach ($candidateIds as $id) {
            $row = $this->subcategoryRows[$id];
            if ($row['category_id'] !== $categoryId) {
                continue;
            }
            if ($this->rowMatchesSegment($row, $segment)) {
                $matches[] = $id;
            }
        }

        return $matches;
    }

    /**
     * @param  list<string>  $subSegments
     * @return ?array{id: int, name_ar: string, name_en: string, name_tr: string}
     */
    private function findCategory(string $segment, array $subSegments, string $currentName, string $newName): ?array
    {
        $matches = [];
        foreach ($this->categoryRows as $row) {
            if ($this->rowMatchesSegment($row, $segment)) {
                $matches[] = $row;
            }
        }

        if (count($matches) === 1) {
            return $matches[0];
        }

        if (count($matches) > 1 && $subSegments !== []) {
            $resolved = [];
            foreach ($matches as $row) {
                if ($this->canResolveSubcategoryPath($row['id'], $subSegments, $currentName, $newName)) {
                    $resolved[] = $row;
                }
            }
            if (count($resolved) === 1) {
                return $resolved[0];
            }
            if (count($resolved) > 1) {
                $matches = $resolved;
            }
        }

        if (count($matches) === 1) {
            return $matches[0];
        }

        $resolved = [];
        foreach ($this->categoryRows as $row) {
            if ($subSegments === []) {
                if ($this->namesMatch($row['name_ar'], $currentName) || $this->namesMatch($row['name_ar'], $segment)) {
                    $resolved[] = $row;
                }
                continue;
            }

            if ($this->canResolveSubcategoryPath($row['id'], $subSegments, $currentName, $newName)) {
                $resolved[] = $row;
            }
        }

        return count($resolved) === 1 ? $resolved[0] : null;
    }

    /**
     * @param  list<string>  $subSegments
     */
    private function canResolveSubcategoryPath(int $categoryId, array $subSegments, string $currentName, string $newName): bool
    {
        $parentId = null;
        foreach ($subSegments as $index => $segment) {
            $isLeaf = $index === count($subSegments) - 1;
            $matches = $this->findSubcategoryChildren($categoryId, $parentId, $segment);

            if (count($matches) > 1) {
                $matches = $this->disambiguateSubcategoryMatches(
                    $matches,
                    $categoryId,
                    $parentId,
                    $isLeaf,
                    $currentName,
                    array_slice($subSegments, $index + 1),
                    $newName,
                );
            }

            if (count($matches) !== 1) {
                if ($isLeaf) {
                    $matches = array_values(array_unique(array_merge(
                        $this->findSubcategoryChildrenByExactName($categoryId, $parentId, $newName),
                        $this->findSubcategoryChildrenByCurrentName($categoryId, $parentId, $currentName),
                    )));
                }

                if (count($matches) !== 1) {
                    return false;
                }
            }

            if ($isLeaf) {
                $nameAr = $this->subcategoryRows[$matches[0]]['name_ar'];

                return $this->namesMatchExact($nameAr, $newName)
                    || $this->namesMatch($nameAr, $currentName);
            }

            $parentId = $matches[0];
        }

        return false;
    }

    /**
     * @param  array{name_ar: string, name_en: string, name_tr: string}  $row
     */
    private function rowMatchesSegment(array $row, string $segment): bool
    {
        foreach (['name_ar', 'name_en', 'name_tr'] as $field) {
            if ($this->namesMatchExact($row[$field], $segment)) {
                return true;
            }
        }

        if ($this->segmentHasArabic($segment)) {
            $segmentKey = $this->arabicMatchKey($segment);
            foreach (['name_ar', 'name_en', 'name_tr'] as $field) {
                if ($this->arabicMatchKey($row[$field]) === $segmentKey) {
                    return true;
                }
            }
        }

        if ($this->isMostlyAscii($segment)) {
            $needle = $this->normalizeName($segment);
            foreach (['name_ar', 'name_en', 'name_tr'] as $field) {
                $haystack = $this->normalizeName($row[$field]);
                if ($haystack === '') {
                    continue;
                }
                if (preg_match('/^'.preg_quote($needle, '/').'(\s|$|-)/iu', $haystack) === 1) {
                    return true;
                }
            }
        }

        return false;
    }

    private function arabicMatchKey(string $text): string
    {
        $text = $this->normalizeName($text);
        $text = str_replace(['أ', 'إ', 'آ'], 'ا', $text);
        $text = preg_replace('/\bال(?=\p{Arabic})/u', '', $text) ?? $text;
        $text = str_replace('وال', 'و', $text);
        $text = preg_replace('/\s+/u', '', $text) ?? $text;

        return $text;
    }

    private function arabicKeysAreSimilar(string $a, string $b): bool
    {
        if ($a === '' || $b === '') {
            return false;
        }

        if ($a === $b) {
            return true;
        }

        if (str_starts_with($a, $b) || str_starts_with($b, $a)) {
            return false;
        }

        $maxLen = max(mb_strlen($a), mb_strlen($b));
        if ($maxLen < 5 || $maxLen > 40) {
            return false;
        }

        return levenshtein($a, $b) <= 1;
    }

    /**
     * @return list<string>
     */
    private function splitPath(string $path): array
    {
        $parts = preg_split('/\s*>\s*/u', $path) ?: [];

        return array_values(array_filter(array_map('trim', $parts), fn (string $p) => $p !== ''));
    }

    private function namesMatch(string $a, string $b): bool
    {
        if ($this->namesMatchExact($a, $b)) {
            return true;
        }

        return $this->arabicKeysAreSimilar($this->arabicMatchKey($a), $this->arabicMatchKey($b));
    }

    private function namesMatchExact(string $a, string $b): bool
    {
        $na = $this->normalizeName($a);
        $nb = $this->normalizeName($b);

        if ($na === $nb) {
            return true;
        }

        if ($this->isMostlyAscii($na) && $this->isMostlyAscii($nb)) {
            return strcasecmp($na, $nb) === 0;
        }

        return false;
    }

    private function segmentHasArabic(string $segment): bool
    {
        return (bool) preg_match('/\p{Arabic}/u', $segment);
    }

    private function normalizeName(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\x{00AD}]/u', '', $name) ?? $name;
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;

        return $name;
    }

    private function isMostlyAscii(string $value): bool
    {
        return (bool) preg_match('/^[\x00-\x7F\s\-&.]+$/', $value);
    }

    /**
     * @param  list<string|null>  $header
     * @return array<string, int>
     */
    private function mapColumns(array $header): array
    {
        $map = [];
        foreach ($header as $index => $label) {
            $label = trim((string) $label);
            if ($label === 'المسار الحالي') {
                $map['path'] = $index;
            } elseif ($label === 'الاسم الحالي') {
                $map['current'] = $index;
            } elseif ($label === 'الاسم الصحيح الجديد') {
                $map['new'] = $index;
            } elseif ($label === 'الإجراء المطلوب') {
                $map['action'] = $index;
            }
        }

        return $map;
    }

    /**
     * @param  list<string|null>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string|null>  $header
     * @return list<string|null>
     */
    private function stripBom(array $header): array
    {
        if (isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]) ?? $header[0];
        }

        return $header;
    }

    /**
     * @param  resource|false|null  $handle
     * @param  ?array{type: string, id: int, old: string, new: string}  $entity
     */
    private function writeReportRow(
        $handle,
        int $line,
        string $status,
        string $message,
        string $path,
        string $current,
        string $new,
        ?array $entity
    ): void {
        if ($handle === false || $handle === null) {
            return;
        }

        fputcsv($handle, [
            $line,
            $status,
            $message,
            $path,
            $current,
            $new,
            $entity['type'] ?? '',
            $entity['id'] ?? '',
            $entity['old'] ?? '',
        ]);
    }

    private function bumpApiCacheVersions(): void
    {
        Cache::forever('api:categories:version', (int) Cache::get('api:categories:version', 1) + 1);
        Cache::forever('api:home:categories:version', (int) Cache::get('api:home:categories:version', 1) + 1);
    }
}
