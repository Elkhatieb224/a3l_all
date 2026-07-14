<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 14, 2); // positive = credit, negative = debit
            $table->string('currency', 3)->default('SYP');
            $table->string('type', 32); // hawala_credit, package_purchase, admin_adjustment
            $table->string('reference_type', 64)->nullable(); // hawala_transfer, subscription
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'currency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
