<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'currency',
        'type',
        'reference_type',
        'reference_id',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public const TYPE_HAWALA_CREDIT = 'hawala_credit';
    public const TYPE_PACKAGE_PURCHASE = 'package_purchase';
    public const TYPE_ADMIN_ADJUSTMENT = 'admin_adjustment';
}
