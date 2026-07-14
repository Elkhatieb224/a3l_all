<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (! Schema::hasColumn('categories', 'ad_images_max')) {
                $table->unsignedInteger('ad_images_max')->nullable()->after('ad_images_mode');
            }
        });

        Schema::table('subcategories', function (Blueprint $table) {
            if (! Schema::hasColumn('subcategories', 'ad_images_max')) {
                $table->unsignedInteger('ad_images_max')->nullable()->after('ad_images_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'ad_images_max')) {
                $table->dropColumn('ad_images_max');
            }
        });

        Schema::table('subcategories', function (Blueprint $table) {
            if (Schema::hasColumn('subcategories', 'ad_images_max')) {
                $table->dropColumn('ad_images_max');
            }
        });
    }
};

