<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $guard = 'admin';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'role',
        'is_active',
        'last_login_at',
        'two_factor_enabled',
        'two_factor_email',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'two_factor_enabled' => 'boolean',
        'last_login_at' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * مطابقة صارمة مع تطبيع (مسافات / حالة الأحرف) لتفادي اختلاف قيمة role في قواعد بيانات قديمة.
     */
    public function normalizedRole(): string
    {
        return strtolower(trim((string) $this->getAttribute('role')));
    }

    public function isSuperAdmin(): bool
    {
        return $this->normalizedRole() === 'super_admin';
    }

    public function isAdmin(): bool
    {
        $r = $this->normalizedRole();

        return in_array($r, ['super_admin', 'admin'], true);
    }

    public function isModerator(): bool
    {
        return $this->normalizedRole() === 'moderator';
    }

    public function isSupportAgent(): bool
    {
        return $this->normalizedRole() === 'support_agent';
    }
}

