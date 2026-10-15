<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractorProfile extends Model
{
    public const DRAFT = 'draft';

    public const SUBMITTED = 'submitted';

    public const CHANGES = 'changes_requested';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    public const SUSPENDED = 'suspended';

    public const REMOVED = 'removed';

    protected $fillable = [
        'user_id',
        'trading_type',
        'legal_name',
        'trading_name',
        'company_number',
        'utr',
        'vat_registered',
        'vat_number',
        'cis_status',
        'employs_staff',
        'address_line',
        'city',
        'postcode',
        'coverage_radius_miles',
        'latitude',
        'longitude',
        'phone',
        'trades',
        'approval_status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
        'submitted_at',
        'terms_accepted_at',
        'privacy_version',
        'paused_at',
        'pause_reason',
        'removed_at',
        'draft_reminder_stage',
        'legacy_contractor',
        'claim_invited_at',
        'claim_token_hash',
        'claim_expires_at',
    ];

    protected $casts = [
        'vat_registered' => 'boolean',
        'employs_staff' => 'boolean',
        'legacy_contractor' => 'boolean',
        'trades' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'public_liability_cover' => 'decimal:2',
        'public_liability_expires_on' => 'date',
        'employers_liability_cover' => 'decimal:2',
        'employers_liability_expires_on' => 'date',
        'gas_safe_expires_on' => 'date',
        'electrical_expires_on' => 'date',
        'reviewed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'terms_accepted_at' => 'datetime',
        'paused_at' => 'datetime',
        'removed_at' => 'datetime',
        'claim_invited_at' => 'datetime',
        'claim_expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ContractorDocument::class);
    }

    public function tradeApprovals(): HasMany
    {
        return $this->hasMany(ContractorTrade::class);
    }

    public function reviewEvents(): HasMany
    {
        return $this->hasMany(ContractorReviewEvent::class)->latest('id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function displayName(): string
    {
        return $this->trading_name ?: ($this->legal_name ?: ($this->user?->name ?: 'Contractor'));
    }

    public function hasTrade(string $trade): bool
    {
        return in_array($trade, $this->trades ?? [], true);
    }

    public function document(string $type): ?ContractorDocument
    {
        return $this->relationLoaded('documents')
            ? $this->documents->firstWhere('type', $type)
            : $this->documents()->where('type', $type)->first();
    }

    public function tradeApproval(string $trade): ?ContractorTrade
    {
        return $this->relationLoaded('tradeApprovals')
            ? $this->tradeApprovals->firstWhere('trade', $trade)
            : $this->tradeApprovals()->where('trade', $trade)->first();
    }

    public function statusLabel(): string
    {
        if ($this->approval_status === self::APPROVED && $this->paused_at) {
            return 'Paused';
        }

        return match ($this->approval_status) {
            self::DRAFT => 'Not submitted',
            self::SUBMITTED => 'Pending review',
            self::CHANGES => 'More info needed',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::SUSPENDED => 'Suspended',
            self::REMOVED => 'Removed',
            default => ucfirst(str_replace('_', ' ', (string) $this->approval_status)),
        };
    }

    public function log(string $action, ?User $actor = null, ?string $subject = null, ?string $note = null, array $snapshot = []): void
    {
        $this->reviewEvents()->create([
            'actor_user_id' => $actor?->id,
            'action' => $action,
            'subject' => $subject,
            'note' => $note,
            'snapshot' => $snapshot ?: null,
        ]);
    }
}
