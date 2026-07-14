<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_ip_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45);
            $table->string('channel', 20);
            $table->unsignedSmallInteger('lockout_cycles')->default(0);
            $table->boolean('is_permanent')->default(false);
            $table->timestamp('blocked_until')->nullable();
            $table->timestamp('last_failed_at')->nullable();
            $table->timestamp('last_lockout_at')->nullable();
            $table->json('attempt_logs')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->unique(['ip_address', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_ip_blocks');
    }
};
