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
        Schema::table('reports', function (Blueprint $table) {
            // Check if conversation_id column doesn't exist before adding
            if (!Schema::hasColumn('reports', 'conversation_id')) {
                $table->foreignId('conversation_id')->nullable()->after('reported_user_id')->constrained()->onDelete('cascade');
            }
            
            // Check if conversation_messages column doesn't exist before adding
            if (!Schema::hasColumn('reports', 'conversation_messages')) {
                $table->json('conversation_messages')->nullable()->after('conversation_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (Schema::hasColumn('reports', 'conversation_id')) {
                $table->dropForeign(['conversation_id']);
                $table->dropColumn('conversation_id');
            }
            
            if (Schema::hasColumn('reports', 'conversation_messages')) {
                $table->dropColumn('conversation_messages');
            }
        });
    }
};
