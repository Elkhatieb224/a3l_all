<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'action',
        'model_type',
        'model_id',
        'changes',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function model()
    {
        return $this->morphTo();
    }

    /**
     * Admin URL to view the subject (for logs table links).
     */
    public function getSubjectUrlAttribute(): ?string
    {
        if (!$this->model_type || !$this->model_id) {
            return null;
        }
        try {
            $model = $this->model_type::find($this->model_id);
            if (!$model) {
                return null;
            }
            if ($model instanceof \App\Models\PackageRequest) {
                return route('admin.package-requests.show', $model->id);
            }
            if ($model instanceof \App\Models\User) {
                return route('admin.users.show', $model->id);
            }
            if ($model instanceof \App\Models\Subscription) {
                return route('admin.users.show', $model->user_id);
            }
            if ($model instanceof \App\Models\Ad) {
                return route('admin.ads.show', $model->uid);
            }
            if ($model instanceof \App\Models\Category) {
                return route('admin.categories.show', $model->id);
            }
            if ($model instanceof \App\Models\Subcategory) {
                return route('admin.subcategories.show', $model->id);
            }
            if ($model instanceof \App\Models\Report) {
                return route('admin.reports.show', $model->id);
            }
            if ($model instanceof \App\Models\Payment) {
                return route('admin.payments.show', $model->id);
            }
            if ($model instanceof \App\Models\HawalaTransferRequest) {
                return route('admin.hawala-transfers.show', $model->id);
            }
            if ($model instanceof \App\Models\VerificationRequest) {
                return route('admin.users.verification-requests.show', $model->id);
            }
            if ($model instanceof \App\Models\Package) {
                return route('admin.packages.index');
            }
            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function log($action, $model = null, $changes = null)
    {
        return self::create([
            'admin_id' => auth('admin')->id(),
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}

