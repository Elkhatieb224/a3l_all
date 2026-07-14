<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportMessageReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'support_message_id',
        'sender_type',
        'user_id',
        'admin_id',
        'message',
    ];

    public function supportMessage()
    {
        return $this->belongsTo(SupportMessage::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function isFromUser()
    {
        return $this->sender_type === 'user';
    }

    public function isFromAdmin()
    {
        return $this->sender_type === 'admin';
    }
}
