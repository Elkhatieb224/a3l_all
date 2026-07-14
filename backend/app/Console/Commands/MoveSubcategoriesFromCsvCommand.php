<?php

namespace App\Console\Commands;

use App\Models\Ad;
use App\Models\Subcategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MoveSubcategoriesFromCsvCommand extends Command
{
    protected $signature = 'subcategories:move-from-csv
                            {file? : Path to the CSV file}
                            {--dry-run : Preview moves without writing to the database}
                            {--create-missing-parents : Create missing target parent nodes when needed}
                            {--report= : Write a detailed report CSV to this path}';

    protected $description = 'Move subcategories to a new parent path from a CSV file and write a report';

    private const ACTION_MOVE = 'نقل القسم';

    /** @var array<int, array{id: int, category_id: int, parent_subcategory_id: ?int, name_ar: string, name_en: string, name_tr: string, slug: string, deleted_at: ?string}> */
    private array $subcategoryRows = [];

    /** @var array<int, list<int>> */
    private array $childrenByParent = [];

    /** @var array<int, list<int>> */
    private array $rootSubcategoryIdsByCategory = [];

    /** @var array<int, array{id: int, name_ar: string, name_en: string, name_tr: string}> */
    private array $categoryRows = [];

    public function handle(): int
    {
        $file = $this->argument('file')
            ?? base_path('../aalenha_final_approved_for_developer (1)/نقل القسم-Table 1.csv');

        if (! is_readable($file)) {
            $this->error("CSV file not readable: {$file}");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $createMissingParents = (bool) $this->option('create-missing-parents');
        $reportPath = $this->option('report')
            ?: storage_path('app/subcategory_move_report.csv');

        $this->loadCatalog();

        $stats = [
            'rows' => 0,
            'moved' => 0,
            'would_move' => 0,
            'removed_duplicate' => 0,
            'would_remove_duplicate' => 0,
            'skipped_already_there' => 0,
            'skipped_action' => 0,
            'not_found' => 0,
            'target_not_found' => 0,
            'name_mismatch' => 0,
            'ambiguous' => 0,
            'duplicate_blocked' => 0,
            'cycle_blocked' => 0,
            'errors' => 0,
            'parents_created' => 0,
            'ads_linked' => 0,
        ];

        $dir = dirname($reportPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $reportHandle = fopen($reportPath, 'wb');
        if ($reportHandle === false) {
            $this->error('Failed to open report file.');

            return self::FAILURE;
        }

        fputcsv($reportHandle, [
            'row_num', 'csv_current_path', 'csv_name', 'csv_target_path', 'status', 'message',
            'subcategory_id', 'db_name_ar', 'slug', 'old_parent_id', 'new_parent_id', 'target_subcategory_id', 'ads_count',
        ]);

        $handle = fopen($file, 'rb');
        if ($handle === false) {
            fclose($reportHandle);
            $this->error('Failed to open CSV file.');

            return self::FAILURE;
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            fclose($reportHandle);
            $this->error('CSV file is empty.');

            return self::FAILURE;
        }

        $header = $this->stripBom($header);
        $columnMap = $this->mapColumns($header);

        if (! isset($columnMap['path'], $columnMap['current'], $columnMap['target'])) {
            fclose($handle);
            fclose($reportHandle);
            $this->error('CSV must include columns: المسار الحالي, الاسم الحالي, المسار النهائي المطلوب');

            return self::FAILURE;
        }

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                if ($row === [null] || $row === false || $this->isEmptyRow($row)) {
                    continue;
                }

                $stats['rows']++;
                $rowNum = trim((string) ($row[$columnMap['row_num']] ?? $stats['rows']));

                $action = isset($columnMap['action'])
                    ? trim((string) ($row[$columnMap['action']] ?? ''))
                    : self::ACTION_MOVE;

                if ($action !== '' && $action !== self::ACTION_MOVE) {
                    $stats['skipped_action']++;
                    fputcsv($reportHandle, [$rowNum, '', '', '', 'skipped_action', 'Unsupported action', '', '', '', '', '', '', 0]);
                    continue;
                }

                $path = trim((string) ($row[$columnMap['path']] ?? ''));
                $currentName = trim((string) ($row[$columnMap['current']] ?? ''));
                $targetPath = trim((string) ($row[$columnMap['target']] ?? ''));

                if ($path === '' || $currentName === '' || $targetPath === '') {
                    $stats['errors']++;
                    fputcsv($reportHandle, [$rowNum, $path, $currentName, $targetPath, 'error', 'Missing path, current name, or target path', '', '', '', '', '', '', 0]);
                    continue;
                }

                $resolve = $this->resolveLeafSubcategoryIds($path, $currentName);
                if ($resolve['status'] !== 'found') {
                    $resolve = $this->resolveLeafSubcategoryIdsByNameAndPath($path, $currentName);
                }
                if ($resolve['status'] !== 'found') {
                    $resolve = $this->resolveLeafSubcategoryIdsBySimilarName($path, $currentName);
                }

                if ($resolve['status'] !== 'found') {
                    $stats[$resolve['stat']]++;
                    fputcsv($reportHandle, [
                        $rowNum, $path, $currentName, $targetPath, $resolve['status'], $resolve['message'],
                        '', '', '', '', '', '', 0,
                    ]);
                    continue;
                }

                if (count($resolve['ids']) !== 1) {
                    $stats['ambiguous']++;
                    fputcsv($reportHandle, [
                        $rowNum, $path, $currentName, $targetPath, 'ambiguous',
                        'Expected exactly one source subcategory, found '.count($resolve['ids']),
                        '', '', '', '', '', '', 0,
                    ]);
                    continue;
                }

                $sourceId = (int) $resolve['ids'][0];
                $moveResult = $this->moveSubcategoryToTarget(
                    $sourceId,
                    $path,
                    $currentName,
                    $targetPath,
                    $dryRun,
                    $createMissingParents,
                );

                $sub = $this->subcategoryRows[$sourceId] ?? null;
                $statKey = $moveResult['stat'];
                if (isset($stats[$statKey])) {
                    $stats[$statKey]++;
                } else {
                    $stats['errors']++;
                }

                if ($moveResult['parents_created'] > 0) {
                    $stats['parents_created'] += $moveResult['parents_created'];
                }

                $stats['ads_linked'] += $moveResult['ads'];

                fputcsv($reportHandle, [
                    $rowNum,
                    $path,
                    $currentName,
                    $targetPath,
                    $moveResult['status'],
                    $moveResult['message'],
                    $sourceId,
                    $sub['name_ar'] ?? '',
                    $sub['slug'] ?? '',
                    $moveResult['old_parent_id'] ?? '',
                    $moveResult['new_parent_id'] ?? '',
                    $moveResult['target_subcategory_id'] ?? '',
                    $moveResult['ads'],
                ]);
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
                if ($stats['moved'] + $stats['removed_duplicate'] + $stats['parents_created'] > 0) {
                    $this->bumpApiCacheVersions();
                }
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            fclose($reportHandle);
            throw $e;
        }

        fclose($handle);
        fclose($reportHandle);

        $this->newLine();
        $this->info($dryRun ? 'DRY RUN — no database changes were saved.' : 'Moves committed to the database.');
        $this->info("Report written to: {$reportPath}");
        $this->table(
            ['Metric', 'Count'],
            collect($stats)->map(fn ($count, $key) => [$key, $count])->values()->all()
        );

        return ($stats['not_found'] + $stats['target_not_found'] + $stats['name_mismatch']
            + $stats['ambiguous'] + $stats['duplicate_blocked'] + $stats['cycle_blocked'] + $stats['errors']) > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * @return array{status: string, stat: string, message: string, ids: list<int>}
     */
    private function resolveLeafSubcategoryIds(string $path, string $currentName): array
    {
        $segments = $this->splitPath($path);

        if ($segments === []) {
            return ['status' => 'error', 'stat' => 'errors', 'message' => 'Empty path', 'ids' => []];
        }

        if ($this->namesMatch($segments[count($segments) - 1], $currentName) === false) {
            return [
                'status' => 'name_mismatch',
                'stat' => 'name_mismatch',
                'message' => 'Path leaf does not match current name column',
                'ids' => [],
            ];
        }

        if (count($segments) === 1) {
            return [
                'status' => 'error',
                'stat' => 'errors',
                'message' => 'Top-level category delete is not supported; expected subcategory path',
                'ids' => [],
            ];
        }

        $category = $this->findCategory($segments[0], array_slice($segments, 1), $currentName);
        if ($category === null) {
            return [
                'status' => 'not_found',
                'stat' => 'not_found',
                'message' => 'Category not found: '.$segments[0],
                'ids' => [],
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
                );
            }

            if ($matches === []) {
                if ($isLeaf) {
                    $matches = $this->findSubcategoryChildrenByCurrentName($category['id'], $parentId, $currentName);
                }

                if ($matches === []) {
                    return [
                        'status' => 'not_found',
                        'stat' => 'not_found',
                        'message' => 'Subcategory not found at segment: '.$segment,
                        'ids' => [],
                    ];
                }
            }

            if (count($matches) > 1 && $isLeaf) {
                $matches = array_values(array_filter(
                    $matches,
                    fn (int $id) => $this->namesMatch($this->subcategoryRows[$id]['name_ar'], $currentName)
                ));

                if ($matches === []) {
                    return [
                        'status' => 'name_mismatch',
                        'stat' => 'name_mismatch',
                        'message' => 'Multiple matches but none match current name',
                        'ids' => [],
                    ];
                }

                return [
                    'status' => 'found',
                    'stat' => 'deleted',
                    'message' => 'Matched '.count($matches).' subcategory node(s) at leaf',
                    'ids' => $matches,
                ];
            }

            if (count($matches) > 1) {
                return [
                    'status' => 'ambiguous',
                    'stat' => 'ambiguous',
                    'message' => 'Multiple subcategories match segment: '.$segment,
                    'ids' => [],
                ];
            }

            $subId = $matches[0];
            $sub = $this->subcategoryRows[$subId];

            if ($isLeaf) {
                if (! $this->namesMatch($sub['name_ar'], $currentName)) {
                    return [
                        'status' => 'name_mismatch',
                        'stat' => 'name_mismatch',
                        'message' => 'DB name_ar does not match current name (id '.$subId.')',
                        'ids' => [],
                    ];
                }

                return [
                    'status' => 'found',
                    'stat' => 'deleted',
                    'message' => 'Matched subcategory id '.$subId,
                    'ids' => [$subId],
                ];
            }

            $parentId = $subId;
        }

        return ['status' => 'error', 'stat' => 'errors', 'message' => 'Unresolved path', 'ids' => []];
    }

    /**
     * Fallback when CSV path segments no longer match DB (renamed parents).
     *
     * @return array{status: string, stat: string, message: string, ids: list<int>}
     */
    private function resolveLeafSubcategoryIdsByNameAndPath(string $path, string $currentName): array
    {
        $segments = $this->splitPath($path);
        if ($segments === []) {
            return ['status' => 'error', 'stat' => 'errors', 'message' => 'Empty path', 'ids' => []];
        }

        $categoryCandidates = $this->findCategoriesBySegment($segments[0]);
        if ($categoryCandidates === []) {
            return [
                'status' => 'not_found',
                'stat' => 'not_found',
                'message' => 'Category not found: '.$segments[0],
                'ids' => [],
            ];
        }

        $resolved = [];
        foreach ($categoryCandidates as $category) {
            $ids = $this->walkSubcategoryPathFuzzy((int) $category['id'], array_slice($segments, 1), $currentName);
            $resolved = array_merge($resolved, $ids);
        }

        $resolved = array_values(array_unique($resolved));

        if ($resolved !== []) {
            return [
                'status' => 'found',
                'stat' => 'deleted',
                'message' => 'Matched by fuzzy path walk (fallback) ids: '.implode(', ', $resolved),
                'ids' => $resolved,
            ];
        }

        $singleNameMatches = [];
        foreach ($categoryCandidates as $category) {
            foreach ($this->subcategoryRows as $id => $row) {
                if ($row['category_id'] !== $category['id'] || $row['deleted_at'] !== null) {
                    continue;
                }
                if ($this->namesMatch($row['name_ar'], $currentName)) {
                    $singleNameMatches[] = $id;
                }
            }
        }

        $singleNameMatches = array_values(array_unique($singleNameMatches));
        if (count($singleNameMatches) === 1) {
            return [
                'status' => 'found',
                'stat' => 'deleted',
                'message' => 'Matched by unique name in category (fallback) id '.$singleNameMatches[0],
                'ids' => [$singleNameMatches[0]],
            ];
        }

        if (count($singleNameMatches) > 1) {
            return [
                'status' => 'ambiguous',
                'stat' => 'ambiguous',
                'message' => 'Multiple subcategories named «'.$currentName.'» in category',
                'ids' => [],
            ];
        }

        return [
            'status' => 'not_found',
            'stat' => 'not_found',
            'message' => 'No subcategory matches current name under candidate categories',
            'ids' => [],
        ];
    }

    /**
     * @return array{status: string, stat: string, message: string, ids: list<int>}
     */
    private function resolveLeafSubcategoryIdsBySimilarName(string $path, string $currentName): array
    {
        $segments = $this->splitPath($path);
        if ($segments === [] || count($segments) < 2) {
            return ['status' => 'not_found', 'stat' => 'not_found', 'message' => 'Invalid path for similar-name lookup', 'ids' => []];
        }

        $categoryCandidates = $this->findCategoriesBySegment($segments[0]);
        if ($categoryCandidates === []) {
            return [
                'status' => 'not_found',
                'stat' => 'not_found',
                'message' => 'Category not found: '.$segments[0],
                'ids' => [],
            ];
        }

        $parentSegments = array_slice($segments, 1, -1);
        $resolved = [];

        foreach ($categoryCandidates as $category) {
            $categoryId = (int) $category['id'];
            $parentIds = $this->resolveParentIdsForSegments($categoryId, $parentSegments, $currentName);

            foreach ($parentIds as $parentId) {
                $resolved = array_merge(
                    $resolved,
                    $this->findSubcategoryChildrenBySimilarName($categoryId, $parentId, $currentName),
                );
            }
        }

        $resolved = array_values(array_unique($resolved));

        if (count($resolved) === 1) {
            return [
                'status' => 'found',
                'stat' => 'found',
                'message' => 'Matched by similar name id '.$resolved[0],
                'ids' => $resolved,
            ];
        }

        if (count($resolved) > 1) {
            return [
                'status' => 'ambiguous',
                'stat' => 'ambiguous',
                'message' => 'Multiple similar-name matches for «'.$currentName.'»',
                'ids' => [],
            ];
        }

        return [
            'status' => 'not_found',
            'stat' => 'not_found',
            'message' => 'No similar-name match for «'.$currentName.'»',
            'ids' => [],
        ];
    }

    /**
     * @param  list<string>  $parentSegments
     * @return list<int|null>
     */
    private function resolveParentIdsForSegments(int $categoryId, array $parentSegments, string $currentName): array
    {
        if ($parentSegments === []) {
            return [null];
        }

        $parentId = null;

        foreach ($parentSegments as $index => $segment) {
            $matches = $this->findSubcategoryChildrenFuzzy($categoryId, $parentId, $segment);
            $isLastParentSegment = $index === count($parentSegments) - 1;

            if ($isLastParentSegment && count($matches) > 1) {
                $filtered = [];
                foreach ($matches as $matchId) {
                    if ($this->findSubcategoryChildrenBySimilarName($categoryId, $matchId, $currentName) !== []
                        || $this->findSubcategoryChildrenByCurrentName($categoryId, $matchId, $currentName) !== []) {
                        $filtered[] = $matchId;
                    }
                }

                if ($filtered !== []) {
                    $matches = array_values(array_unique($filtered));
                }
            }

            if (count($matches) !== 1) {
                return [];
            }

            $parentId = $matches[0];
        }

        return [$parentId];
    }

    /**
     * @return list<int>
     */
    private function findSubcategoryChildrenBySimilarName(int $categoryId, ?int $parentId, string $currentName): array
    {
        $candidateIds = $parentId === null
            ? ($this->rootSubcategoryIdsByCategory[$categoryId] ?? [])
            : ($this->childrenByParent[$parentId] ?? []);

        $matches = [];
        foreach ($candidateIds as $id) {
            $row = $this->subcategoryRows[$id];
            if ($row['category_id'] !== $categoryId || $row['deleted_at'] !== null) {
                continue;
            }
            if ($this->namesMatch($row['name_ar'], $currentName)
                || $this->namesAreSimilarForMove($row['name_ar'], $currentName)
                || $this->namesAreSimilarForMove($row['name_en'], $currentName)) {
                $matches[] = $id;
            }
        }

        return $matches;
    }

    /**
     * @param  list<string>  $subSegments
     * @return list<int>
     */
    private function walkSubcategoryPathFuzzy(int $categoryId, array $subSegments, string $currentName): array
    {
        if ($subSegments === []) {
            return [];
        }

        $parentId = null;

        foreach ($subSegments as $index => $segment) {
            $isLeaf = $index === count($subSegments) - 1;
            $matches = $this->findSubcategoryChildrenFuzzy($categoryId, $parentId, $segment);

            if ($matches === [] && $isLeaf) {
                $matches = $this->findSubcategoryChildrenByCurrentName($categoryId, $parentId, $currentName);
            }

            if ($isLeaf) {
                return array_values(array_filter(
                    $matches,
                    fn (int $id) => $this->namesMatch($this->subcategoryRows[$id]['name_ar'], $currentName)
                ));
            }

            if (count($matches) !== 1) {
                return [];
            }

            $parentId = $matches[0];
        }

        return [];
    }

    /**
     * @return list<int>
     */
    private function findSubcategoryChildrenFuzzy(int $categoryId, ?int $parentId, string $segment): array
    {
        $candidateIds = $parentId === null
            ? ($this->rootSubcategoryIdsByCategory[$categoryId] ?? [])
            : ($this->childrenByParent[$parentId] ?? []);

        $matches = [];
        foreach ($candidateIds as $id) {
            $row = $this->subcategoryRows[$id];
            if ($row['category_id'] !== $categoryId || $row['deleted_at'] !== null) {
                continue;
            }
            if ($this->rowMatchesSegment($row, $segment) || $this->segmentMatchesName($segment, $row['name_ar'])) {
                $matches[] = $id;
            }
        }

        return $matches;
    }

    /**
     * @return list<array{id: int, name_ar: string, name_en: string, name_tr: string}>
     */
    private function findCategoriesBySegment(string $segment): array
    {
        $matches = [];
        foreach ($this->categoryRows as $row) {
            if ($this->categoryMatchesSegment($row, $segment)) {
                $matches[] = $row;
            }
        }

        return $matches;
    }

    /**
     * @param  array{name_ar: string, name_en: string, name_tr: string}  $row
     */
    private function categoryMatchesSegment(array $row, string $segment): bool
    {
        if ($this->rowMatchesSegment($row, $segment)) {
            return true;
        }

        return $this->arabicWordSetKey($row['name_ar']) === $this->arabicWordSetKey($segment);
    }

    private function segmentMatchesName(string $segment, string $name): bool
    {
        if ($this->rowMatchesSegment(['name_ar' => $name, 'name_en' => $name, 'name_tr' => $name], $segment)
            || $this->namesMatch($name, $segment)
            || $this->arabicWordSetKey($name) === $this->arabicWordSetKey($segment)) {
            return true;
        }

        $segmentKey = $this->arabicMatchKey($segment);
        $nameKey = $this->arabicMatchKey($name);

        if ($segmentKey !== '' && $nameKey !== '' && str_contains($nameKey, $segmentKey)) {
            return true;
        }

        if (str_contains($segment, 'للبيع') && str_contains($name, 'للبيع')) {
            if (str_contains($segment, 'مع') && ! str_contains($name, 'مع')) {
                return false;
            }

            return true;
        }

        if (str_contains($segment, 'تنازل') && str_contains($name, 'فروغ') && ! str_contains($segment, 'للبيع')) {
            return ! str_contains($name, 'للبيع');
        }

        if (str_contains($segment, 'فروغ') && str_contains($name, 'فروغ')) {
            return true;
        }

        if (str_contains($segment, 'ATV') && str_contains($name, 'ATV')) {
            return true;
        }

        if (str_contains($segment, 'دراج') && str_contains($name, 'دراج') && str_contains($name, 'رباع')) {
            return true;
        }

        if (str_contains($segment, 'عقار تجاري') && $this->namesMatch($name, 'تجاري')) {
            return true;
        }

        if (str_contains($segment, 'للبيع مع') && str_contains($name, 'للبيع')) {
            return true;
        }

        return false;
    }

    private function arabicWordSetKey(string $text): string
    {
        $text = $this->normalizeName($text);
        $text = str_replace(['أ', 'إ', 'آ'], 'ا', $text);
        $parts = preg_split('/\s+/u', $text) ?: [];
        $words = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $part = preg_replace('/^ال(?=\p{Arabic})/u', '', $part) ?? $part;
            $part = ltrim($part, 'و');
            $part = preg_replace('/\s+/u', '', $part) ?? $part;
            if ($part !== '') {
                $words[] = $part;
            }
        }

        sort($words);

        return implode('|', $words);
    }

    /**
     * @return array{status: string, stat: string, message: string, old_parent_id: ?int, new_parent_id: ?int, target_subcategory_id: ?int, ads: int, parents_created: int}
     */
    private function moveSubcategoryToTarget(
        int $sourceId,
        string $currentPath,
        string $currentName,
        string $targetPath,
        bool $dryRun,
        bool $createMissingParents,
    ): array {
        if (! isset($this->subcategoryRows[$sourceId])) {
            return $this->moveResult('not_found', 'not_found', 'Source subcategory not in catalog', $sourceId);
        }

        $source = $this->subcategoryRows[$sourceId];
        if ($source['deleted_at'] !== null) {
            return $this->moveResult('not_found', 'not_found', 'Source subcategory is deleted', $sourceId);
        }

        $oldParentId = $source['parent_subcategory_id'];
        $targetResolve = $this->resolveTargetParent($targetPath, $currentName, $createMissingParents, $dryRun);

        if ($targetResolve['status'] !== 'ok') {
            return $this->moveResult(
                $targetResolve['status'],
                $targetResolve['stat'],
                $targetResolve['message'],
                $sourceId,
                $oldParentId,
                null,
                null,
                0,
                $targetResolve['parents_created'] ?? 0,
            );
        }

        $newParentId = $targetResolve['parent_id'];
        $targetCategoryId = $targetResolve['category_id'];
        $parentsCreated = (int) ($targetResolve['parents_created'] ?? 0);
        $existingTargetId = $this->findSiblingByName($newParentId, $targetCategoryId, $source, $sourceId);

        if ($existingTargetId !== null) {
            return $this->removeDuplicateSource($sourceId, $existingTargetId, $dryRun, $oldParentId, $newParentId);
        }

        if ($source['parent_subcategory_id'] === $newParentId && $source['category_id'] === $targetCategoryId) {
            return $this->moveResult(
                'skipped_already_there',
                'skipped_already_there',
                'Already under target parent id '.($newParentId ?? 'category-root'),
                $sourceId,
                $oldParentId,
                $newParentId,
            );
        }

        if ($newParentId !== null && in_array($newParentId, $this->getDescendantIdsFromCatalog($sourceId), true)) {
            return $this->moveResult(
                'cycle_blocked',
                'cycle_blocked',
                'Target parent is a descendant of the source subcategory',
                $sourceId,
                $oldParentId,
                $newParentId,
            );
        }

        $adsCount = (int) Ad::query()->where('subcategory_id', $sourceId)->count();

        if ($dryRun) {
            return $this->moveResult(
                'would_move',
                'would_move',
                'Would move to parent id '.($newParentId ?? 'category-root').' in category '.$targetCategoryId,
                $sourceId,
                $oldParentId,
                $newParentId,
                null,
                $adsCount,
                $parentsCreated,
            );
        }

        $model = Subcategory::query()->find($sourceId);
        if ($model === null) {
            return $this->moveResult('not_found', 'not_found', 'Source subcategory not found in DB', $sourceId);
        }

        $model->category_id = $targetCategoryId;
        $model->parent_subcategory_id = $newParentId;
        $model->saveQuietly();

        $this->reindexSubcategoryInCatalog($sourceId, $oldParentId, $newParentId, $targetCategoryId);

        return $this->moveResult(
            'moved',
            'moved',
            'Moved to parent id '.($newParentId ?? 'category-root').' in category '.$targetCategoryId,
            $sourceId,
            $oldParentId,
            $newParentId,
            null,
            $adsCount,
            $parentsCreated,
        );
    }

    /**
     * @return array{status: string, stat: string, message: string, parent_id: ?int, category_id: int, parents_created: int}
     */
    private function resolveTargetParent(
        string $targetPath,
        string $leafName,
        bool $createMissingParents,
        bool $dryRun,
    ): array {
        $segments = $this->splitPath($targetPath);
        if ($segments === [] || count($segments) < 2) {
            return [
                'status' => 'target_not_found',
                'stat' => 'target_not_found',
                'message' => 'Target path is empty or too short',
                'parent_id' => null,
                'category_id' => 0,
                'parents_created' => 0,
            ];
        }

        $lastSegment = $segments[count($segments) - 1];
        if (! $this->namesMatch($lastSegment, $leafName) && ! $this->namesAreSimilarForMove($lastSegment, $leafName)) {
            return [
                'status' => 'name_mismatch',
                'stat' => 'name_mismatch',
                'message' => 'Target path leaf does not match current name column',
                'parent_id' => null,
                'category_id' => 0,
                'parents_created' => 0,
            ];
        }

        $parentSegments = array_slice($segments, 0, -1);
        $category = $this->findCategory($parentSegments[0], array_slice($parentSegments, 1), $leafName);

        if ($category === null) {
            return [
                'status' => 'target_not_found',
                'stat' => 'target_not_found',
                'message' => 'Target category not found: '.$parentSegments[0],
                'parent_id' => null,
                'category_id' => 0,
                'parents_created' => 0,
            ];
        }

        $categoryId = (int) $category['id'];
        $subSegments = array_slice($parentSegments, 1);
        $parentId = null;
        $parentsCreated = 0;

        foreach ($subSegments as $segment) {
            $matches = $this->findSubcategoryChildren($categoryId, $parentId, $segment);

            if (count($matches) > 1) {
                $matches = $this->disambiguateSubcategoryMatches(
                    $matches,
                    $categoryId,
                    $parentId,
                    false,
                    $leafName,
                    [],
                );
            }

            if ($matches === []) {
                $matches = $this->findSubcategoryChildrenFuzzy($categoryId, $parentId, $segment);
            }

            if ($matches === [] && $createMissingParents) {
                $createdId = $this->createMissingParentNode($categoryId, $parentId, $segment, $dryRun);
                if ($createdId === null) {
                    return [
                        'status' => 'target_not_found',
                        'stat' => 'target_not_found',
                        'message' => 'Failed to create missing target parent: '.$segment,
                        'parent_id' => null,
                        'category_id' => $categoryId,
                        'parents_created' => $parentsCreated,
                    ];
                }

                $parentsCreated++;
                $parentId = $createdId;
                continue;
            }

            if (count($matches) !== 1) {
                return [
                    'status' => 'target_not_found',
                    'stat' => 'target_not_found',
                    'message' => count($matches) === 0
                        ? 'Target parent segment not found: '.$segment
                        : 'Ambiguous target parent segment: '.$segment,
                    'parent_id' => null,
                    'category_id' => $categoryId,
                    'parents_created' => $parentsCreated,
                ];
            }

            $parentId = $matches[0];
        }

        return [
            'status' => 'ok',
            'stat' => 'ok',
            'message' => 'Resolved target parent',
            'parent_id' => $parentId,
            'category_id' => $categoryId,
            'parents_created' => $parentsCreated,
        ];
    }

    private function createMissingParentNode(int $categoryId, ?int $parentId, string $segmentName, bool $dryRun): ?int
    {
        $template = $this->findTemplateSubcategoryBySegment($categoryId, $segmentName);

        $nameAr = $template['name_ar'] ?? $segmentName;
        $nameEn = $template['name_en'] ?? $segmentName;
        $nameTr = $template['name_tr'] ?? $segmentName;
        $baseSlug = $template['slug'] ?? \Illuminate\Support\Str::slug($segmentName);
        $slug = $this->makeUniqueSlug($baseSlug, $parentId);

        if ($dryRun) {
            $fakeId = -1 * (count($this->subcategoryRows) + 1);
            $this->registerSubcategoryInCatalog([
                'id' => $fakeId,
                'category_id' => $categoryId,
                'parent_subcategory_id' => $parentId,
                'name_ar' => $nameAr,
                'name_en' => $nameEn,
                'name_tr' => $nameTr,
                'slug' => $slug,
                'deleted_at' => null,
            ]);

            return $fakeId;
        }

        $model = Subcategory::query()->create([
            'category_id' => $categoryId,
            'parent_subcategory_id' => $parentId,
            'name_ar' => $nameAr,
            'name_en' => $nameEn,
            'name_tr' => $nameTr,
            'slug' => $slug,
            'order' => 0,
            'is_active' => true,
        ]);

        $this->registerSubcategoryInCatalog([
            'id' => (int) $model->id,
            'category_id' => $categoryId,
            'parent_subcategory_id' => $parentId,
            'name_ar' => $nameAr,
            'name_en' => $nameEn,
            'name_tr' => $nameTr,
            'slug' => $slug,
            'deleted_at' => null,
        ]);

        return (int) $model->id;
    }

    /**
     * @return ?array{name_ar: string, name_en: string, name_tr: string, slug: string}
     */
    private function findTemplateSubcategoryBySegment(int $categoryId, string $segmentName): ?array
    {
        foreach ($this->subcategoryRows as $row) {
            if ($row['deleted_at'] !== null || $row['category_id'] !== $categoryId) {
                continue;
            }
            if ($this->namesMatch($row['name_ar'], $segmentName)
                || $this->namesMatch($row['name_en'], $segmentName)
                || $this->rowMatchesSegment($row, $segmentName)) {
                return [
                    'name_ar' => $row['name_ar'],
                    'name_en' => $row['name_en'],
                    'name_tr' => $row['name_tr'],
                    'slug' => $row['slug'],
                ];
            }
        }

        return null;
    }

    private function makeUniqueSlug(string $baseSlug, ?int $parentId): string
    {
        $baseSlug = trim($baseSlug) !== '' ? trim($baseSlug) : 'subcategory';

        if ($parentId !== null && isset($this->subcategoryRows[$parentId])) {
            $parentSlug = $this->subcategoryRows[$parentId]['slug'];
            $candidate = $parentSlug.'-'.$baseSlug;
            if (! $this->slugExists($candidate)) {
                return $candidate;
            }
        }

        $slug = $baseSlug;
        $suffix = 2;

        while ($this->slugExists($slug)) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function slugExists(string $slug): bool
    {
        foreach ($this->subcategoryRows as $row) {
            if ($row['deleted_at'] === null && $row['slug'] === $slug) {
                return true;
            }
        }

        return Subcategory::query()->where('slug', $slug)->exists();
    }

    /**
     * @param  array{id: int, category_id: int, parent_subcategory_id: ?int, name_ar: string, name_en: string, name_tr: string, slug: string, deleted_at: ?string}  $row
     */
    private function registerSubcategoryInCatalog(array $row): void
    {
        $id = (int) $row['id'];
        $this->subcategoryRows[$id] = $row;

        if ($row['deleted_at'] !== null) {
            return;
        }

        if ($row['parent_subcategory_id'] === null) {
            $this->rootSubcategoryIdsByCategory[$row['category_id']][] = $id;
        } else {
            $this->childrenByParent[$row['parent_subcategory_id']][] = $id;
        }
    }

    private function reindexSubcategoryInCatalog(int $id, ?int $oldParentId, ?int $newParentId, int $categoryId): void
    {
        if (! isset($this->subcategoryRows[$id])) {
            return;
        }

        $oldCategoryId = $this->subcategoryRows[$id]['category_id'];

        if ($oldParentId === null) {
            $this->rootSubcategoryIdsByCategory[$oldCategoryId] = array_values(array_filter(
                $this->rootSubcategoryIdsByCategory[$oldCategoryId] ?? [],
                fn (int $childId) => $childId !== $id,
            ));
        } else {
            $this->childrenByParent[$oldParentId] = array_values(array_filter(
                $this->childrenByParent[$oldParentId] ?? [],
                fn (int $childId) => $childId !== $id,
            ));
        }

        $this->subcategoryRows[$id]['parent_subcategory_id'] = $newParentId;
        $this->subcategoryRows[$id]['category_id'] = $categoryId;

        if ($newParentId === null) {
            $this->rootSubcategoryIdsByCategory[$categoryId][] = $id;
        } else {
            $this->childrenByParent[$newParentId][] = $id;
        }
    }

    /**
     * @param  array{name_ar: string, name_en: string}  $source
     */
    private function findSiblingByName(?int $parentId, int $categoryId, array $source, int $excludeId): ?int
    {
        $candidateIds = $parentId === null
            ? ($this->rootSubcategoryIdsByCategory[$categoryId] ?? [])
            : ($this->childrenByParent[$parentId] ?? []);

        foreach ($candidateIds as $id) {
            if ($id === $excludeId) {
                continue;
            }
            $row = $this->subcategoryRows[$id];
            if ($row['deleted_at'] !== null || $row['category_id'] !== $categoryId) {
                continue;
            }
            if ($this->siblingNamesMatch($row, $source)) {
                return $id;
            }
        }

        return null;
    }

    /**
     * @param  array{name_ar: string, name_en: string, name_tr: string}  $row
     * @param  array{name_ar: string, name_en: string, name_tr: string}  $source
     */
    private function siblingNamesMatch(array $row, array $source): bool
    {
        foreach (['name_ar', 'name_en', 'name_tr'] as $field) {
            if ($this->namesMatchExact($row[$field], $source[$field])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{status: string, stat: string, message: string, old_parent_id: ?int, new_parent_id: ?int, target_subcategory_id: ?int, ads: int, parents_created: int}
     */
    private function removeDuplicateSource(
        int $sourceId,
        int $targetSubcategoryId,
        bool $dryRun,
        ?int $oldParentId,
        ?int $newParentId,
    ): array {
        $descendants = $this->getDescendantIdsFromCatalog($sourceId);
        $childCount = count($descendants) - 1;
        $adsCount = (int) Ad::query()->whereIn('subcategory_id', $descendants)->count();

        if ($childCount > 0) {
            return $this->moveResult(
                'duplicate_blocked',
                'duplicate_blocked',
                'Target already has this subcategory (id '.$targetSubcategoryId.') and source has children',
                $sourceId,
                $oldParentId,
                $newParentId,
                $targetSubcategoryId,
                $adsCount,
            );
        }

        if ($adsCount > 0 && $dryRun) {
            return $this->moveResult(
                'would_remove_duplicate',
                'would_remove_duplicate',
                'Would reassign '.$adsCount.' ad(s) to id '.$targetSubcategoryId.' then remove duplicate id '.$sourceId,
                $sourceId,
                $oldParentId,
                $newParentId,
                $targetSubcategoryId,
                $adsCount,
            );
        }

        if ($adsCount > 0) {
            Ad::query()->where('subcategory_id', $sourceId)->update(['subcategory_id' => $targetSubcategoryId]);
        }

        if ($dryRun) {
            return $this->moveResult(
                'would_remove_duplicate',
                'would_remove_duplicate',
                'Would remove duplicate id '.$sourceId.'; keep id '.$targetSubcategoryId,
                $sourceId,
                $oldParentId,
                $newParentId,
                $targetSubcategoryId,
                $adsCount,
            );
        }

        $model = Subcategory::query()->find($sourceId);
        if ($model !== null && ! $model->trashed()) {
            $model->slug = $this->buildDeletedSlug((string) $model->slug, $sourceId);
            $model->saveQuietly();
            $model->delete();
            $this->subcategoryRows[$sourceId]['deleted_at'] = now()->toDateTimeString();
            $this->removeSubcategoryFromCatalogIndexes($sourceId, $oldParentId, $this->subcategoryRows[$sourceId]['category_id']);
        }

        return $this->moveResult(
            'removed_duplicate',
            'removed_duplicate',
            'Removed duplicate id '.$sourceId.'; kept id '.$targetSubcategoryId,
            $sourceId,
            $oldParentId,
            $newParentId,
            $targetSubcategoryId,
            $adsCount,
        );
    }

    private function removeSubcategoryFromCatalogIndexes(int $id, ?int $parentId, int $categoryId): void
    {
        if ($parentId === null) {
            $this->rootSubcategoryIdsByCategory[$categoryId] = array_values(array_filter(
                $this->rootSubcategoryIdsByCategory[$categoryId] ?? [],
                fn (int $childId) => $childId !== $id,
            ));
        } else {
            $this->childrenByParent[$parentId] = array_values(array_filter(
                $this->childrenByParent[$parentId] ?? [],
                fn (int $childId) => $childId !== $id,
            ));
        }
    }

    /**
     * @return list<int>
     */
    private function getDescendantIdsFromCatalog(int $rootId): array
    {
        $ids = [$rootId];
        $stack = [$rootId];

        while ($stack !== []) {
            $current = array_pop($stack);
            foreach ($this->childrenByParent[$current] ?? [] as $childId) {
                if (isset($this->subcategoryRows[$childId]) && $this->subcategoryRows[$childId]['deleted_at'] === null) {
                    $ids[] = $childId;
                    $stack[] = $childId;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return array{status: string, stat: string, message: string, old_parent_id: ?int, new_parent_id: ?int, target_subcategory_id: ?int, ads: int, parents_created: int}
     */
    private function moveResult(
        string $status,
        string $stat,
        string $message,
        int $sourceId,
        ?int $oldParentId = null,
        ?int $newParentId = null,
        ?int $targetSubcategoryId = null,
        int $ads = 0,
        int $parentsCreated = 0,
    ): array {
        return [
            'status' => $status,
            'stat' => $stat,
            'message' => $message,
            'old_parent_id' => $oldParentId,
            'new_parent_id' => $newParentId,
            'target_subcategory_id' => $targetSubcategoryId,
            'ads' => $ads,
            'parents_created' => $parentsCreated,
        ];
    }

    private function namesAreSimilarForMove(string $a, string $b): bool
    {
        if ($this->namesMatchExact($a, $b) || $this->namesMatch($a, $b)) {
            return true;
        }

        if (! $this->isMostlyAscii($a) || ! $this->isMostlyAscii($b)) {
            return false;
        }

        $na = strtolower(preg_replace('/\s+/u', '', $this->normalizeName($a)) ?? '');
        $nb = strtolower(preg_replace('/\s+/u', '', $this->normalizeName($b)) ?? '');

        if ($na === $nb) {
            return true;
        }

        if ($na === '' || $nb === '') {
            return false;
        }

        $maxLen = max(strlen($na), strlen($nb));

        return $maxLen >= 8 && $maxLen <= 60 && levenshtein($na, $nb) <= 2;
    }

    private function buildDeletedSlug(string $originalSlug, int $subcategoryId): string
    {
        $suffix = '--deleted-'.$subcategoryId;
        $maxBase = 255 - strlen($suffix);
        $base = substr($originalSlug, 0, max(1, $maxBase));

        return rtrim($base, '-').$suffix;
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
        foreach (DB::table('subcategories')->select(['id', 'category_id', 'parent_subcategory_id', 'name_ar', 'name_en', 'name_tr', 'slug', 'deleted_at'])->cursor() as $row) {
            $id = (int) $row->id;
            $this->subcategoryRows[$id] = [
                'id' => $id,
                'category_id' => (int) $row->category_id,
                'parent_subcategory_id' => $row->parent_subcategory_id !== null ? (int) $row->parent_subcategory_id : null,
                'name_ar' => (string) $row->name_ar,
                'name_en' => (string) $row->name_en,
                'name_tr' => (string) $row->name_tr,
                'slug' => (string) $row->slug,
                'deleted_at' => $row->deleted_at !== null ? (string) $row->deleted_at : null,
            ];
        }

        $this->childrenByParent = [];
        $this->rootSubcategoryIdsByCategory = [];

        foreach ($this->subcategoryRows as $id => $row) {
            if ($row['deleted_at'] !== null) {
                continue;
            }
            if ($row['parent_subcategory_id'] === null) {
                $this->rootSubcategoryIdsByCategory[$row['category_id']][] = $id;
            } else {
                $this->childrenByParent[$row['parent_subcategory_id']][] = $id;
            }
        }
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
            if ($this->canResolveFromParent($categoryId, $id, $remainingSegments, $currentName)) {
                $filtered[] = $id;
            }
        }

        return $filtered !== [] ? $filtered : $matches;
    }

    /**
     * @param  list<string>  $segments
     */
    private function canResolveFromParent(int $categoryId, int $parentId, array $segments, string $currentName): bool
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
                );
            }

            if (count($children) !== 1) {
                if ($isLeaf) {
                    $children = $this->findSubcategoryChildrenByCurrentName($categoryId, $currentParent, $currentName);
                }

                if (count($children) !== 1) {
                    return false;
                }
            }

            if ($isLeaf) {
                return $this->namesMatch($this->subcategoryRows[$children[0]]['name_ar'], $currentName);
            }

            $currentParent = $children[0];
        }

        return false;
    }

    /**
     * @param  list<string>  $subSegments
     * @return ?array{id: int, name_ar: string, name_en: string, name_tr: string}
     */
    private function findCategory(string $segment, array $subSegments, string $currentName): ?array
    {
        $matches = $this->findCategoriesBySegment($segment);

        if (count($matches) === 1) {
            return $matches[0];
        }

        if (count($matches) > 1 && $subSegments !== []) {
            $resolved = [];
            foreach ($matches as $row) {
                if ($this->canResolveSubcategoryPath($row['id'], $subSegments, $currentName)) {
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
            if ($this->canResolveSubcategoryPath($row['id'], $subSegments, $currentName)) {
                $resolved[] = $row;
            }
        }

        return count($resolved) === 1 ? $resolved[0] : null;
    }

    /**
     * @param  list<string>  $subSegments
     */
    private function canResolveSubcategoryPath(int $categoryId, array $subSegments, string $currentName): bool
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
                );
            }

            if (count($matches) !== 1) {
                if ($isLeaf) {
                    $matches = $this->findSubcategoryChildrenByCurrentName($categoryId, $parentId, $currentName);
                }

                if (count($matches) !== 1) {
                    return false;
                }
            }

            if ($isLeaf) {
                return $this->namesMatch($this->subcategoryRows[$matches[0]]['name_ar'], $currentName);
            }

            $parentId = $matches[0];
        }

        return false;
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
            if ($row['category_id'] !== $categoryId || $row['deleted_at'] !== null) {
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
            if ($row['category_id'] !== $categoryId || $row['deleted_at'] !== null) {
                continue;
            }
            if ($this->rowMatchesSegment($row, $segment)) {
                $matches[] = $id;
            }
        }

        return $matches;
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
            if ($label === 'رقم') {
                $map['row_num'] = $index;
            } elseif ($label === 'المسار الحالي') {
                $map['path'] = $index;
            } elseif ($label === 'الاسم الحالي') {
                $map['current'] = $index;
            } elseif ($label === 'المسار النهائي المطلوب') {
                $map['target'] = $index;
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

    private function bumpApiCacheVersions(): void
    {
        Cache::forever('api:categories:version', (int) Cache::get('api:categories:version', 1) + 1);
        Cache::forever('api:home:categories:version', (int) Cache::get('api:home:categories:version', 1) + 1);
    }
}
