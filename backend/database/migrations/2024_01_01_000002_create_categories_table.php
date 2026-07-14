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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('name_tr');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_tr')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('subcategories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('name_tr');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_tr')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subcategories');
        Schema::dropIfExists('categories');
    }
};

