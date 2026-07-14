<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'search_term',
        'ip_address',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Save or update search history
     */
    public static function saveSearch($searchTerm, $userId = null)
    {
        // Don't save empty searches
        if (empty(trim($searchTerm))) {
            return null;
        }

        $searchTerm = trim($searchTerm);

        // If user is logged in, check if this search already exists for this user
        if ($userId) {
            $existing = self::where('user_id', $userId)
                ->where('search_term', $searchTerm)
                ->latest()
                ->first();

            if ($existing) {
                // Update the timestamp to move it to the top
                $existing->touch();
                return $existing;
            }
        }

        // Create new search history
        return self::create([
            'user_id' => $userId,
            'search_term' => $searchTerm,
            'ip_address' => request()->ip(),
        ]);
    }
}
