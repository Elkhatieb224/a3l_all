<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dynamic_regions', function (Blueprint $table) {
            $table->id();
            $table->string('country', 2)->index();
            $table->string('anchor_state_code', 64)->nullable()->index();
            $table->foreignId('parent_id')->nullable()->constrained('dynamic_regions')->nullOnDelete();
            $table->string('type', 16);
            $table->string('code', 64)->unique();
            $table->string('dedup_hash', 64)->index();
            $table->string('name_ar', 255)->nullable();
            $table->string('name_en', 255)->nullable();
            $table->string('name_tr', 255)->nullable();
            $table->json('extra_match_names')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('use_count')->default(0);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dynamic_regions');
    }
};
