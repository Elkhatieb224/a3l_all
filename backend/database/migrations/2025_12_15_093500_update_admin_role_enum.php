<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
    * Run the migrations.
    */
    public function up(): void
    {
        // Add support_agent to role enum
        DB::statement("ALTER TABLE admins MODIFY role ENUM('super_admin','admin','moderator','support_agent') DEFAULT 'admin'");
    }

    /**
    * Reverse the migrations.
    */
    public function down(): void
    {
        // Revert to original roles
        DB::statement("ALTER TABLE admins MODIFY role ENUM('super_admin','admin','moderator') DEFAULT 'admin'");
    }
};

