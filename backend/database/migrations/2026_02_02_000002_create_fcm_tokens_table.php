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
        Schema::create('fcm_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('token'); // FCM tokens can be long, use text
            $table->string('token_hash', 64)->unique(); // Hash for unique constraint (avoids MySQL key length limit)
            $table->string('device_type')->nullable(); // android, ios, web
            $table->string('device_id')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fcm_tokens');
    }
};
