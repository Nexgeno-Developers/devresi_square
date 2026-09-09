<?php

namespace App\Models;

use App\Support\AccountMembership;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;
use RuntimeException;

class AccountUser extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'account_id',
        'user_id',
        'member_type',
        'access_level',
        'can_login',
        'branch_id',
        'designation_id',
        'status',
        'created_by',
    ];

    protected $casts = [
        'can_login' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (AccountUser $membership) {
            $user = User::query()->find($membership->user_id);

            if ($user?->isSuperAdmin()) {
                throw new RuntimeException('Super Admin users must not be added to customer accounts.');
            }
        });
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isWorkspaceAdmin(): bool
    {
        return AccountMembership::isWorkspaceAdmin($this->member_type);
    }

    public function isPortalMember(): bool
    {
        return AccountMembership::isPortalType($this->member_type);
    }

    public function isTenant(): bool
    {
        return AccountMembership::isTenant($this->member_type);
    }
}
