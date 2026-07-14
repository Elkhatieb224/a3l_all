<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            if (! Schema::hasColumn('ads', 'location_state')) {
                $table->string('location_state', 255)->nullable()->after('location_country');
            }
            if (! Schema::hasColumn('ads', 'location_state_code')) {
                $table->string('location_state_code', 64)->nullable()->after('location_state');
            }
            if (! Schema::hasColumn('ads', 'location_city_code')) {
                $table->string('location_city_code', 64)->nullable()->after('location_city');
            }
            if (! Schema::hasColumn('ads', 'location_district_code')) {
                $table->string('location_district_code', 64)->nullable()->after('location_district');
            }
            if (! Schema::hasColumn('ads', 'location_input_method')) {
                $table->string('location_input_method', 16)->default('manual')->after('longitude');
            }
            if (! Schema::hasColumn('ads', 'show_location')) {
                $table->boolean('show_location')->default(true)->after('location_input_method');
            }
        });

        if (Schema::hasColumn('ads', 'location_city') && Schema::hasColumn('ads', 'location_state')) {
            DB::table('ads')->orderBy('id')->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    if (($row->location_state ?? null) !== null && $row->location_state !== '') {
                        continue;
                    }
                    $oldCity = $row->location_city ?? null;
                    if ($oldCity === null || $oldCity === '') {
                        continue;
                    }
                    DB::table('ads')->where('id', $row->id)->update([
                        'location_state' => $oldCity,
                        'location_city' => null,
                    ]);
                }
            });
        }

        if (! Schema::hasTable('ads')) {
            return;
        }

        $indexExists = DB::selectOne(
            'SELECT COUNT(1) AS c FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            ['ads', 'ads_location_hierarchy_idx']
        );
        if ($indexExists && (int) $indexExists->c === 0) {
            DB::statement('ALTER TABLE `ads` ADD INDEX `ads_location_hierarchy_idx` (`location_country`(48), `location_state_code`(16), `location_city_code`(16), `location_district_code`(16))');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ads')) {
            try {
                DB::statement('ALTER TABLE `ads` DROP INDEX `ads_location_hierarchy_idx`');
            } catch (\Throwable) {
            }
        }
        Schema::table('ads', function (Blueprint $table) {
            $cols = [
                'location_state',
                'location_state_code',
                'location_city_code',
                'location_district_code',
                'location_input_method',
                'show_location',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('ads', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
