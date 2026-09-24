<?php

namespace App\Models;

use App\Traits\BelongsToSaasAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class TenancyCorrectionRequest extends Model
{
    use BelongsToSaasAccount;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'account_id',
        'tenancy_id',
        'tenant_member_id',
        'requested_by',
        'status',
        'message',
        'fields',
        'snapshot',
        'landlord_note',
        'reviewed_by',
        'reviewed_at',
        'applied_at',
    ];

    protected $casts = [
        'fields' => 'array',
        'snapshot' => 'array',
        'reviewed_at' => 'datetime',
        'applied_at' => 'datetime',
    ];

    public function tenancy(): BelongsTo
    {
        return $this->belongsTo(Tenancy::class);
    }

    public function tenantMember(): BelongsTo
    {
        return $this->belongsTo(TenantMember::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function property(): HasOneThrough
    {
        return $this->hasOneThrough(
            Property::class,
            Tenancy::class,
            'id',
            'id',
            'tenancy_id',
            'property_id',
        );
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
