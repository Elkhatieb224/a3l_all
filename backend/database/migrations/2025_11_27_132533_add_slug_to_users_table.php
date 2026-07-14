<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
            $table->index('slug');
        });

        // Generate slugs for existing users
        User::chunk(100, function ($users) {
            foreach ($users as $user) {
                $user->slug = $this->generateUniqueSlug($user->name, $user->id);
                $user->save();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropColumn('slug');
        });
    }

    /**
     * Generate unique slug
     */
    private function generateUniqueSlug($name, $userId)
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (User::where('slug', $slug)->where('id', '!=', $userId)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
};
