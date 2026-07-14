<?php

namespace Database\Seeders;

use App\Models\GeoDivision;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GeoDivisionsSeeder extends Seeder
{
    private int $syStateId = 1000;

    public function run(): void
    {
        $trCatalog = config_path('ad_regions_tr.php');
        if (is_file($trCatalog) && filesize($trCatalog) > 1_000_000) {
            @ini_set('memory_limit', '768M');
        }

        Schema::disableForeignKeyConstraints();
        DB::table('geo_divisions')->truncate();
        Schema::enableForeignKeyConstraints();

        $trTree = require $trCatalog;
        $this->seedStatesOnly('TR', $trTree, true);

        $syTree = require config_path('ad_regions_sy.php');
        $this->seedStatesOnly('SY', $syTree, false);

        // مدن/أحياء تستخدم insertGetId؛ بدون هذا العداد يستهلك 2..34 قبل TR-35 فيتصادم مع id المحافظة 35
        $this->bumpAutoIncrementPastStateIds();

        $this->seedCitiesAndDistricts('TR', $trTree, true);
        $this->seedCitiesAndDistricts('SY', $syTree, false);
    }

    private function bumpAutoIncrementPastStateIds(): void
    {
        $max = (int) DB::table('geo_divisions')->max('id');
        $next = max($max + 1, 100000);
        DB::statement('ALTER TABLE `geo_divisions` AUTO_INCREMENT = '.$next);
    }

    /**
     * محافظات فقط (معرّفات صريحة 1–81 لـ TR و 1000+ لـ SY).
     *
     * @param  list<array<string, mixed>>  $tree
     */
    private function seedStatesOnly(string $country, array $tree, bool $useTrPlateIds): void
    {
        $stateOrder = 0;
        foreach ($tree as $stateNode) {
            $stateOrder++;
            $stateId = $useTrPlateIds
                ? $this->trPlateFromStateCode((string) ($stateNode['code'] ?? ''))
                : $this->syStateId++;

            if ($stateId === null) {
                throw new \InvalidArgumentException('Invalid TR state code: '.($stateNode['code'] ?? ''));
            }

            $this->insertRow(
                $stateId,
                $country,
                null,
                GeoDivision::LEVEL_STATE,
                (string) ($stateNode['code'] ?? ''),
                $stateOrder,
                $stateNode
            );
        }
    }

    /**
     * @param  list<array<string, mixed>>  $tree
     */
    private function seedCitiesAndDistricts(string $country, array $tree, bool $useTrPlateIds): void
    {
        $stateOrder = 0;
        foreach ($tree as $stateNode) {
            $stateOrder++;
            $stateId = $useTrPlateIds
                ? $this->trPlateFromStateCode((string) ($stateNode['code'] ?? ''))
                : $this->syStateIdForOrder($country, $stateOrder);

            if ($stateId === null) {
                throw new \InvalidArgumentException('Invalid TR state code: '.($stateNode['code'] ?? ''));
            }

            $cityOrder = 0;
            foreach ($stateNode['cities'] ?? [] as $cityNode) {
                $cityOrder++;
                $cityId = DB::table('geo_divisions')->insertGetId([
                    'country' => $country,
                    'parent_id' => $stateId,
                    'level' => GeoDivision::LEVEL_DISTRICT,
                    'code' => (string) ($cityNode['code'] ?? ''),
                    'sort_order' => $cityOrder,
                    'name_ar' => $cityNode['name_ar'] ?? null,
                    'name_en' => $cityNode['name_en'] ?? null,
                    'name_tr' => $cityNode['name_tr'] ?? null,
                    'extra_match_names' => json_encode($cityNode['match_names'] ?? []),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $distOrder = 0;
                foreach ($cityNode['districts'] ?? [] as $distNode) {
                    $distOrder++;
                    GeoDivision::query()->insert([
                        'country' => $country,
                        'parent_id' => $cityId,
                        'level' => GeoDivision::LEVEL_NEIGHBORHOOD,
                        'code' => (string) ($distNode['code'] ?? ''),
                        'sort_order' => $distOrder,
                        'name_ar' => $distNode['name_ar'] ?? null,
                        'name_en' => $distNode['name_en'] ?? null,
                        'name_tr' => $distNode['name_tr'] ?? null,
                        'extra_match_names' => json_encode($distNode['match_names'] ?? []),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * يعيد نفس تسلسل معرّفات SY المستخدم في seedStatesOnly (1000، 1001، …).
     */
    private function syStateIdForOrder(string $country, int $stateOrder): ?int
    {
        if ($country !== 'SY') {
            return null;
        }

        $base = 1000;

        return $base + $stateOrder - 1;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function insertRow(
        int $id,
        string $country,
        ?int $parentId,
        int $level,
        string $code,
        int $sortOrder,
        array $node
    ): void {
        DB::table('geo_divisions')->insert([
            'id' => $id,
            'country' => $country,
            'parent_id' => $parentId,
            'level' => $level,
            'code' => $code,
            'sort_order' => $sortOrder,
            'name_ar' => $node['name_ar'] ?? null,
            'name_en' => $node['name_en'] ?? null,
            'name_tr' => $node['name_tr'] ?? null,
            'extra_match_names' => json_encode($node['match_names'] ?? []),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function trPlateFromStateCode(string $code): ?int
    {
        if (preg_match('/^TR-(\d{1,2})$/', $code, $m)) {
            return (int) $m[1];
        }

        return null;
    }
}
