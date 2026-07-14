<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'message',
        'documents',
        'status',
        'reviewed_by',
        'admin_notes',
        'reviewed_at',
        'business_name',
        'business_type',
        'responsible_person',
        'business_address',
        'business_phone',
        'instagram_url',
        'facebook_url',
        'website_url',
        'primary_document_type',
        'primary_document_path',
        'storefront_image_path',
    ];

    protected $casts = [
        'documents' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }
}
