<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('package_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('admins')->onDelete('set null');
            $table->foreignId('subscription_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_requests');
    }
};
