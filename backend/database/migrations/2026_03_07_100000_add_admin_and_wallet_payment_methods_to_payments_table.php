<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM(
            'credit_card',
            'bank_transfer',
            'paypal',
            'stripe',
            'other',
            'admin_grant',
            'admin_approval',
            'wallet'
        ) DEFAULT 'credit_card'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum (existing rows with new values would need to be updated first)
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM(
            'credit_card',
            'bank_transfer',
            'paypal',
            'stripe',
            'other'
        ) DEFAULT 'credit_card'");
    }
};
