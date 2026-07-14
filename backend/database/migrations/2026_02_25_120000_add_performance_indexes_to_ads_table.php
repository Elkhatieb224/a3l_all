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
        Schema::table('ads', function (Blueprint $table) {
            $table->index(['status', 'is_featured', 'published_at'], 'ads_status_featured_published_idx');
            $table->index(['status', 'subcategory_id', 'published_at'], 'ads_status_subcategory_published_idx');
            $table->index(['status', 'is_urgent', 'published_at'], 'ads_status_urgent_published_idx');
            $table->index(['status', 'price'], 'ads_status_price_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropIndex('ads_status_featured_published_idx');
            $table->dropIndex('ads_status_subcategory_published_idx');
            $table->dropIndex('ads_status_urgent_published_idx');
            $table->dropIndex('ads_status_price_idx');
        });
    }
};
