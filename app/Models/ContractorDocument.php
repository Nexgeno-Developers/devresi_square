<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractorDocument extends Model
{
    public const PHOTO_ID = 'photo_id';

    public const PROOF_OF_ADDRESS = 'proof_of_address';

    public const PUBLIC_LIABILITY = 'public_liability';

    public const EMPLOYERS_LIABILITY = 'employers_liability';

    public const GAS_SAFE = 'gas_safe';

    public const ELECTRICAL_SCHEME = 'electrical_scheme';

    public const OFTEC = 'oftec';

    public const FGAS = 'fgas';

    public const DBS = 'dbs';

    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    public const PURGED = 'purged';

    protected $fillable = [
        'contractor_profile_id',
        'type',
        'status',
        'reference',
        'issuer',
        'cover_amount',
        'disk',
        'path',
        'original_name',
        'mime',
        'encrypted',
        'expires_at',
        'reminder_stage',
        'expired_notified_at',
        'submitted_at',
        'verified_at',
        'verified_by',
        'rejected_reason',
    ];

    protected $casts = [
        'cover_amount' => 'decimal:2',
        'encrypted' => 'boolean',
        'expires_at' => 'datetime',
        'expired_notified_at' => 'datetime',
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ContractorProfile::class, 'contractor_profile_id');
    }

    public function label(): string
    {
        return config('contractor_marketplace.documents.'.$this->type.'.label', ucfirst(str_replace('_', ' ', $this->type)));
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->copy()->endOfDay()->isPast();
    }

    public function isValid(): bool
    {
        if ($this->status !== self::APPROVED || $this->isExpired()) {
            return false;
        }

        return ! config('contractor_marketplace.documents.'.$this->type.'.expires') || $this->expires_at !== null;
    }
}
