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
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('subcategory_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->decimal('price', 15, 2)->nullable();
            $table->enum('price_type', ['fixed', 'negotiable', 'free'])->default('fixed');
            $table->string('location_country')->default('TR');
            $table->string('location_city');
            $table->string('location_district')->nullable();
            $table->string('location_address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->json('images')->nullable();
            $table->json('custom_fields')->nullable();
            $table->enum('status', ['pending', 'active', 'rejected', 'expired', 'sold'])->default('pending');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->integer('views_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_urgent')->default(false);
            $table->string('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'published_at']);
            $table->index(['category_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};

