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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('ad_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('reported_user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->enum('type', ['spam', 'fraud', 'inappropriate', 'duplicate', 'other'])->default('other');
            $table->text('reason');
            $table->enum('status', ['pending', 'reviewed', 'resolved', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('admins')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'created_at']);
            $table->index('ad_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};

