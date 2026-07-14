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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('name_tr');
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_tr')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('TRY');
            $table->integer('duration_days')->default(30);
            $table->integer('ads_limit')->default(10);
            $table->boolean('featured_ads')->default(false);
            $table->boolean('urgent_ads')->default(false);
            $table->boolean('priority_support')->default(false);
            $table->boolean('homepage_display')->default(false);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('package_id')->constrained()->onDelete('cascade');
            $table->timestamp('starts_at');
            $table->timestamp('expires_at');
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->integer('ads_used')->default(0);
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['expires_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('packages');
    }
};

