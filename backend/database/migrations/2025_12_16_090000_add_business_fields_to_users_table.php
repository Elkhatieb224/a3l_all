<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('business_name')->nullable()->after('name');
            $table->string('business_type')->nullable()->after('business_name');
            $table->string('business_owner')->nullable()->after('business_type');
            $table->string('business_address')->nullable()->after('business_owner');
            $table->string('business_phone')->nullable()->after('business_address');
            $table->string('instagram_url')->nullable()->after('business_phone');
            $table->string('facebook_url')->nullable()->after('instagram_url');
            $table->string('website_url')->nullable()->after('facebook_url');
            $table->string('storefront_image_path')->nullable()->after('website_url');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'business_name',
                'business_type',
                'business_owner',
                'business_address',
                'business_phone',
                'instagram_url',
                'facebook_url',
                'website_url',
                'storefront_image_path',
            ]);
        });
    }
};

