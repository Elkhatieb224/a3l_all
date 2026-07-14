<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            if (! Schema::hasColumn('admins', 'two_factor_enabled')) {
                $table->boolean('two_factor_enabled')->default(false)->after('last_login_at');
            }
            if (! Schema::hasColumn('admins', 'two_factor_email')) {
                $table->string('two_factor_email')->nullable()->after('two_factor_enabled');
            }
        });

        Schema::create('admin_two_factor_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('email');
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['admin_id', 'type']);
        });

        Schema::create('admin_two_factor_ip_trusts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->string('ip_address', 45);
            $table->timestamp('trusted_until');
            $table->timestamps();

            $table->unique(['admin_id', 'ip_address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_two_factor_ip_trusts');
        Schema::dropIfExists('admin_two_factor_challenges');

        Schema::table('admins', function (Blueprint $table) {
            if (Schema::hasColumn('admins', 'two_factor_email')) {
                $table->dropColumn('two_factor_email');
            }
            if (Schema::hasColumn('admins', 'two_factor_enabled')) {
                $table->dropColumn('two_factor_enabled');
            }
        });
    }
};
