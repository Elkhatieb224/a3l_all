<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageRequest extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'package_id',
        'status',
        'admin_response',
        'responded_at',
        'responded_by',
        'subscription_id',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function responder()
    {
        return $this->belongsTo(Admin::class, 'responded_by');
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
