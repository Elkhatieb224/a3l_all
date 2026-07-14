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
        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('subject');
            $table->longText('message');
            $table->enum('status', ['pending', 'in_progress', 'resolved', 'closed'])->default('pending');
            $table->foreignId('admin_id')->nullable()->constrained()->onDelete('set null');
            $table->longText('admin_response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_messages');
    }
};
