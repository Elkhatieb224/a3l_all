<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HawalaTransferRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'currency',
        'receipt_number',
        'receipt_image_path',
        'note',
        'status',
        'admin_credited_amount',
        'admin_credited_currency',
        'approved_at',
        'approved_by',
        'rejection_reason',
        'package_id',
        'subscription_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'admin_credited_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
}
