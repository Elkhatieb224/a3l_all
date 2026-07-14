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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('package_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('subscription_id')->nullable()->constrained()->onDelete('set null');
            $table->string('transaction_id')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('TRY');
            $table->enum('payment_method', ['credit_card', 'bank_transfer', 'paypal', 'stripe', 'other'])->default('credit_card');
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->json('payment_details')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

