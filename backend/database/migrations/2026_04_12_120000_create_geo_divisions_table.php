<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_divisions', function (Blueprint $table) {
            $table->id();
            $table->string('country', 2)->index();
            $table->foreignId('parent_id')->nullable()->constrained('geo_divisions')->cascadeOnDelete();
            $table->unsignedTinyInteger('level')->index();
            $table->string('code', 64)->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('name_ar', 255)->nullable();
            $table->string('name_en', 255)->nullable();
            $table->string('name_tr', 255)->nullable();
            $table->json('extra_match_names')->nullable();
            $table->timestamps();

            $table->index(['country', 'parent_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_divisions');
    }
};
