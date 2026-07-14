<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('packages', 'featured_ads_limit')) {
            Schema::table('packages', function (Blueprint $table) {
                $table->unsignedInteger('featured_ads_limit')->default(0)->after('urgent_ads');
                $table->unsignedInteger('urgent_ads_limit')->default(0)->after('featured_ads_limit');
            });
        }

        if (!Schema::hasColumn('subscriptions', 'featured_ads_used')) {
            // Fix expires_at invalid default (0000-00-00) before altering table - required for MySQL strict mode
            \DB::statement("ALTER TABLE subscriptions MODIFY COLUMN expires_at TIMESTAMP NULL DEFAULT NULL");
            \DB::statement('ALTER TABLE subscriptions ADD COLUMN featured_ads_used INT UNSIGNED NOT NULL DEFAULT 0');
            \DB::statement('ALTER TABLE subscriptions ADD COLUMN urgent_ads_used INT UNSIGNED NOT NULL DEFAULT 0');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('packages', 'featured_ads_limit')) {
            Schema::table('packages', function (Blueprint $table) {
                $table->dropColumn(['featured_ads_limit', 'urgent_ads_limit']);
            });
        }

        if (Schema::hasColumn('subscriptions', 'featured_ads_used')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropColumn(['featured_ads_used', 'urgent_ads_used']);
            });
        }
    }
};
