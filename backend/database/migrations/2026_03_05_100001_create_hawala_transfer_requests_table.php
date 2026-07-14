<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hawala_transfer_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('SYP');
            $table->string('receipt_number');
            $table->string('receipt_image_path')->nullable();
            $table->string('status', 20)->default('pending'); // pending, approved, rejected
            $table->decimal('admin_credited_amount', 14, 2)->nullable();
            $table->string('admin_credited_currency', 3)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hawala_transfer_requests');
    }
};
