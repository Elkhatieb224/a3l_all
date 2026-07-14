<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_search_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saved_search_id')->constrained('saved_searches')->cascadeOnDelete();
            $table->foreignId('ad_id')->constrained('ads')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['saved_search_id', 'ad_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_search_matches');
    }
};

