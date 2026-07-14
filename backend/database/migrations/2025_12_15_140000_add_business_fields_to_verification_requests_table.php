<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_requests', function (Blueprint $table) {
            $table->string('business_name')->nullable()->after('message');
            $table->string('business_type')->nullable()->after('business_name');
            $table->string('responsible_person')->nullable()->after('business_type');
            $table->string('business_address')->nullable()->after('responsible_person');
            $table->string('business_phone')->nullable()->after('business_address');
            $table->string('instagram_url')->nullable()->after('business_phone');
            $table->string('facebook_url')->nullable()->after('instagram_url');
            $table->string('website_url')->nullable()->after('facebook_url');
            $table->enum('primary_document_type', ['id', 'cr'])->nullable()->after('website_url');
            $table->string('primary_document_path')->nullable()->after('primary_document_type');
            $table->string('storefront_image_path')->nullable()->after('primary_document_path');
        });
    }

    public function down(): void
    {
        Schema::table('verification_requests', function (Blueprint $table) {
            $table->dropColumn([
                'business_name',
                'business_type',
                'responsible_person',
                'business_address',
                'business_phone',
                'instagram_url',
                'facebook_url',
                'website_url',
                'primary_document_type',
                'primary_document_path',
                'storefront_image_path',
            ]);
        });
    }
};

