<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedSearchMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'saved_search_id',
        'ad_id',
    ];

    public function savedSearch()
    {
        return $this->belongsTo(SavedSearch::class);
    }

    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }
}

