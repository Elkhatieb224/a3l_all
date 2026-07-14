<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->string('uid', 9)->unique()->nullable()->after('id');
        });

        // Generate UIDs for existing ads
        $ads = DB::table('ads')->whereNull('uid')->get();
        foreach ($ads as $ad) {
            $uid = $this->generateUniqueUid();
            DB::table('ads')->where('id', $ad->id)->update(['uid' => $uid]);
        }

        // Make uid required after populating existing records
        Schema::table('ads', function (Blueprint $table) {
            $table->string('uid', 9)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropColumn('uid');
        });
    }

    /**
     * Generate a unique 9-digit UID
     */
    private function generateUniqueUid(): string
    {
        do {
            $uid = str_pad(rand(100000000, 999999999), 9, '0', STR_PAD_LEFT);
        } while (DB::table('ads')->where('uid', $uid)->exists());

        return $uid;
    }
};
