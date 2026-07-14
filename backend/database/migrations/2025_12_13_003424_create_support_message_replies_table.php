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
        Schema::create('support_message_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_message_id')->constrained()->onDelete('cascade');
            $table->enum('sender_type', ['user', 'admin'])->default('user');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('admin_id')->nullable()->constrained()->onDelete('cascade');
            $table->longText('message');
            $table->timestamps();
            
            $table->index('support_message_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_message_replies');
    }
};
