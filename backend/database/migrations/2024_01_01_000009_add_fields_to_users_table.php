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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('avatar')->nullable()->after('phone');
            $table->text('bio')->nullable()->after('avatar');
            $table->string('location_country')->default('TR')->after('bio');
            $table->string('location_city')->nullable()->after('location_country');
            $table->boolean('is_verified')->default(false)->after('location_city');
            $table->boolean('is_active')->default(true)->after('is_verified');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone', 'avatar', 'bio', 'location_country', 
                'location_city', 'is_verified', 'is_active', 
                'last_login_at', 'phone_verified_at'
            ]);
            $table->dropSoftDeletes();
        });
    }
};

