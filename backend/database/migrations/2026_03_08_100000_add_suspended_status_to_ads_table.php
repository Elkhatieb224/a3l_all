<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE ads MODIFY COLUMN status ENUM('pending', 'active', 'rejected', 'expired', 'sold', 'suspended') DEFAULT 'pending'");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE ads DROP CONSTRAINT IF EXISTS ads_status_check");
            DB::statement("ALTER TABLE ads ADD CONSTRAINT ads_status_check CHECK (status::text = ANY (ARRAY['pending', 'active', 'rejected', 'expired', 'sold', 'suspended']::text[]))");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("UPDATE ads SET status = 'active' WHERE status = 'suspended'");
            DB::statement("ALTER TABLE ads MODIFY COLUMN status ENUM('pending', 'active', 'rejected', 'expired', 'sold') DEFAULT 'pending'");
        }
    }
};
