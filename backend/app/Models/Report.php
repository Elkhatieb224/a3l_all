<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Conversation;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ad_id',
        'reported_user_id',
        'conversation_id',
        'conversation_messages',
        'type',
        'reason',
        'images',
        'status',
        'admin_notes',
        'admin_attachments',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'conversation_messages' => 'array',
        'images' => 'array',
        'admin_attachments' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }

    public function reportedUser()
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}

