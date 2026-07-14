<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Negotiation extends Model
{
    use HasFactory;

    protected $fillable = [
        'ad_id',
        'buyer_id',
        'seller_id',
        'offered_price',
        'currency',
        'message',
        'status',
        'rejection_reason',
        'conversation_id',
        'responded_at',
    ];

    protected $casts = [
        'offered_price' => 'decimal:2',
        'responded_at' => 'datetime',
    ];

    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}
