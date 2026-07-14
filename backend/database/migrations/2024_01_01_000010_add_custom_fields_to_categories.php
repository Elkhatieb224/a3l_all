<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add custom_fields to categories
        Schema::table('categories', function (Blueprint $table) {
            $table->json('custom_fields')->nullable()->after('description_tr');
        });

        // Add custom_fields and parent_id to subcategories for multi-level support
        Schema::table('subcategories', function (Blueprint $table) {
            $table->json('custom_fields')->nullable()->after('description_tr');
            $table->foreignId('parent_subcategory_id')->nullable()->after('category_id')->constrained('subcategories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('custom_fields');
        });

        Schema::table('subcategories', function (Blueprint $table) {
            $table->dropForeign(['parent_subcategory_id']);
            $table->dropColumn(['custom_fields', 'parent_subcategory_id']);
        });
    }
};

