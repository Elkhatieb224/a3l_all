<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (! Schema::hasColumn('categories', 'ad_images_mode')) {
                $table->string('ad_images_mode', 32)->default('user_upload');
            }
            if (! Schema::hasColumn('categories', 'ad_gallery_images')) {
                $table->json('ad_gallery_images')->nullable();
            }
        });

        Schema::table('subcategories', function (Blueprint $table) {
            if (! Schema::hasColumn('subcategories', 'ad_images_mode')) {
                $table->string('ad_images_mode', 32)->nullable();
            }
            if (! Schema::hasColumn('subcategories', 'ad_gallery_images')) {
                $table->json('ad_gallery_images')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $drop = array_values(array_filter([
                Schema::hasColumn('categories', 'ad_images_mode') ? 'ad_images_mode' : null,
                Schema::hasColumn('categories', 'ad_gallery_images') ? 'ad_gallery_images' : null,
            ]));
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });

        Schema::table('subcategories', function (Blueprint $table) {
            $drop = array_values(array_filter([
                Schema::hasColumn('subcategories', 'ad_images_mode') ? 'ad_images_mode' : null,
                Schema::hasColumn('subcategories', 'ad_gallery_images') ? 'ad_gallery_images' : null,
            ]));
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
