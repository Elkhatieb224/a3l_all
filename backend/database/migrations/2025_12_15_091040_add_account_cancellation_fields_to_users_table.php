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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('scheduled_deletion_at')->nullable()->after('last_login_at');
            $table->enum('account_status', ['active', 'pending_deletion', 'deleted'])->default('active')->after('scheduled_deletion_at');
        });
        
        Schema::table('ads', function (Blueprint $table) {
            $table->enum('account_status', ['active', 'deleted_account'])->default('active')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['scheduled_deletion_at', 'account_status']);
        });
        
        Schema::table('ads', function (Blueprint $table) {
            $table->dropColumn('account_status');
        });
    }
};
