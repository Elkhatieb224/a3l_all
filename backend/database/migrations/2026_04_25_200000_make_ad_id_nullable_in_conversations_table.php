<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function dropAdIdForeignKeyIfExists(): void
    {
        $dbName = DB::getDatabaseName();
        $row = DB::selectOne(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = 'conversations'
               AND COLUMN_NAME = 'ad_id'
               AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1",
            [$dbName]
        );

        if ($row && isset($row->CONSTRAINT_NAME)) {
            DB::statement("ALTER TABLE conversations DROP FOREIGN KEY `{$row->CONSTRAINT_NAME}`");
        }
    }

    private function tryAddAdForeignKey(string $onDelete): void
    {
        if (!Schema::hasTable('ads')) {
            return;
        }

        try {
            DB::statement("ALTER TABLE conversations ADD CONSTRAINT conversations_ad_id_foreign FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE {$onDelete}");
        } catch (\Throwable $e) {
            // Some environments report ads table as existing but still refuse FK creation (e.g. storage engine mismatch).
            // Keep migration non-blocking because ad_id nullability is the core requirement.
        }
    }

    public function up(): void
    {
        if (!Schema::hasTable('conversations') || !Schema::hasColumn('conversations', 'ad_id')) {
            return;
        }

        // MySQL-safe alteration to allow seller chats without linking to an ad.
        $this->dropAdIdForeignKeyIfExists();
        DB::statement('ALTER TABLE conversations MODIFY ad_id BIGINT UNSIGNED NULL');
        $this->tryAddAdForeignKey('SET NULL');
    }

    public function down(): void
    {
        if (!Schema::hasTable('conversations') || !Schema::hasColumn('conversations', 'ad_id')) {
            return;
        }

        DB::statement('DELETE FROM conversations WHERE ad_id IS NULL');
        $this->dropAdIdForeignKeyIfExists();
        DB::statement('ALTER TABLE conversations MODIFY ad_id BIGINT UNSIGNED NOT NULL');
        $this->tryAddAdForeignKey('CASCADE');
    }
};

