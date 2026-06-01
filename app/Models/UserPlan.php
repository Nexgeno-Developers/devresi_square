<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserPlan extends Model
{
    use HasFactory;

    protected $table = 'subscriptions';

    protected $fillable = [
        'company_id', 'plan_id', 'status', 'billing_cycle',
        'starts_at', 'ends_at', 'activated_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function activatedBy()
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') return false;
        return is_null($this->ends_at) || now()->lte($this->ends_at);
    }

    public function daysRemaining(): int
    {
        if (!$this->ends_at) return 0;
        return max(0, (int) now()->diffInDays($this->ends_at, false));
    }
}
