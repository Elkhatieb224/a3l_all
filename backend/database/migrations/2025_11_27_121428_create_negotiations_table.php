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
        Schema::create('negotiations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained()->onDelete('cascade');
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->decimal('offered_price', 15, 2);
            $table->string('currency', 3)->default('SYP');
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('conversation_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            
            $table->index(['ad_id', 'buyer_id']);
            $table->index(['seller_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('negotiations');
    }
};
