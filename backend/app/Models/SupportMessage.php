<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'subject',
        'message',
        'attachments',
        'status',
        'admin_id',
        'admin_response',
        'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
        'attachments' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function replies()
    {
        return $this->hasMany(SupportMessageReply::class)->orderBy('created_at', 'asc');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function markAsResponded($adminId, $response)
    {
        $this->update([
            'status' => 'resolved',
            'admin_id' => $adminId,
            'admin_response' => $response,
            'responded_at' => now(),
        ]);
    }
}
