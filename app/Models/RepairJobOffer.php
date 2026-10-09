<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RepairJobOffer extends Model
{
    public const INVITED = 'invited';

    public const VIEWED = 'viewed';

    public const DECLINED = 'declined';

    public const WITHDRAWN = 'withdrawn';

    public const EXPIRED = 'expired';

    public const OFFER_SENT = 'offer_sent';

    public const INSPECTION_ACCEPTED = 'inspection_accepted';

    public const OFFER_WITHDRAWN = 'offer_withdrawn';

    public const OFFER_EXPIRED = 'offer_expired';

    public const NOT_SELECTED = 'not_selected';

    public const JOB_ACCEPTED = 'job_accepted';

    public const CLOSED = 'closed';

    public const CANCELLED = 'cancelled';

    public const AWAITING_INVITE = [self::INVITED, self::VIEWED];

    public const OPEN_STATUSES = [
        self::INVITED,
        self::VIEWED,
        self::OFFER_SENT,
        self::INSPECTION_ACCEPTED,
    ];

    public const BLOCKING_STATUSES = [
        self::INVITED,
        self::VIEWED,
        self::OFFER_SENT,
        self::INSPECTION_ACCEPTED,
        self::JOB_ACCEPTED,
    ];

    public const JOB_ACCEPTED_STATE = 'accepted';

    public const JOB_SCHEDULED = 'scheduled';

    public const JOB_IN_PROGRESS = 'in_progress';

    public const JOB_COMPLETED = 'completed';

    public const JOB_CONFIRMED = 'confirmed';

    public const JOB_INVOICED = 'invoiced';

    public const PRICING_TYPES = [
        'fixed' => 'Fixed price',
        'hourly' => 'Hourly',
        'callout_hourly' => 'Call-out + hourly',
        'inspection' => 'Inspection first',
    ];

    protected $fillable = [
        'account_id',
        'repair_issue_id',
        'repair_contractor_request_id',
        'contractor_user_id',
        'invited_by',
        'status',
        'job_status',
        'pricing_type',
        'vat_treatment',
        'vat_rate',
        'net_amount',
        'vat_amount',
        'gross_amount',
        'hourly_rate',
        'minimum_hours',
        'call_out_fee',
        'estimated_hours',
        'price_cap',
        'inspection_fee',
        'materials',
        'materials_estimate',
        'price_is_estimate',
        'offer_note',
        'decline_reason',
        'decline_code',
        'withdraw_reason',
        'revision',
        'availability_snapshot',
        'visit_slots',
        'invited_at',
        'viewed_at',
        'invite_expires_at',
        'offer_sent_at',
        'offer_expires_at',
        'decided_at',
        'compliance_notified_at',
        'details_changed_at',
        'inspection_accepted_at',
        'reconfirm_requested_at',
        'reminded_at',
        'digested_at',
        'scheduled_for',
        'started_at',
        'completed_at',
        'completion_note',
        'completion_photos',
        'confirmed_at',
        'tenant_confirmed_at',
        'reschedule_requested_at',
        'reschedule_requested_by',
        'reschedule_slots',
        'reschedule_note',
        'disputed_at',
        'dispute_reason',
        'cancel_reason',
        'rating',
        'rating_comment',
        'rated_at',
    ];

    protected $casts = [
        'vat_rate' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'minimum_hours' => 'decimal:2',
        'call_out_fee' => 'decimal:2',
        'estimated_hours' => 'decimal:2',
        'price_cap' => 'decimal:2',
        'inspection_fee' => 'decimal:2',
        'materials_estimate' => 'decimal:2',
        'price_is_estimate' => 'boolean',
        'visit_slots' => 'array',
        'availability_snapshot' => 'datetime',
        'invited_at' => 'datetime',
        'viewed_at' => 'datetime',
        'invite_expires_at' => 'datetime',
        'offer_sent_at' => 'datetime',
        'offer_expires_at' => 'datetime',
        'decided_at' => 'datetime',
        'compliance_notified_at' => 'datetime',
        'details_changed_at' => 'datetime',
        'inspection_accepted_at' => 'datetime',
        'reconfirm_requested_at' => 'datetime',
        'reminded_at' => 'datetime',
        'digested_at' => 'datetime',
        'scheduled_for' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'tenant_confirmed_at' => 'datetime',
        'completion_photos' => 'array',
        'reschedule_requested_at' => 'datetime',
        'reschedule_slots' => 'array',
        'disputed_at' => 'datetime',
        'rating' => 'integer',
        'rated_at' => 'datetime',
    ];

    public function repairIssue(): BelongsTo
    {
        return $this->belongsTo(RepairIssue::class);
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(RepairContractorRequest::class, 'repair_contractor_request_id');
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contractor_user_id');
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(RepairJobOfferEvent::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(RepairJobMessage::class)->oldest('id');
    }

    public function variations(): HasMany
    {
        return $this->hasMany(RepairJobVariation::class)->latest('id');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(RepairJobInvoice::class);
    }

    /** Agreed price plus every variation the landlord approved. */
    public function finalGross(): float
    {
        return round((float) $this->gross_amount + (float) $this->variations->where('status', RepairJobVariation::APPROVED)->sum('gross_amount'), 2);
    }

    public function isFinished(): bool
    {
        return in_array($this->job_status, [self::JOB_COMPLETED, self::JOB_CONFIRMED, self::JOB_INVOICED], true);
    }

    public function canMessage(): bool
    {
        return in_array($this->status, [...self::OPEN_STATUSES, self::JOB_ACCEPTED], true);
    }

    public function isAwaitingResponse(): bool
    {
        return in_array($this->status, self::AWAITING_INVITE, true);
    }

    public function canQuote(): bool
    {
        return in_array($this->status, [self::INVITED, self::VIEWED, self::OFFER_SENT, self::INSPECTION_ACCEPTED], true);
    }

    /** Full address and tenant contact are only shared once the landlord commits to this contractor. */
    public function unlocksAccess(): bool
    {
        if ($this->status === self::JOB_ACCEPTED) {
            return true;
        }

        return $this->inspection_accepted_at !== null
            && in_array($this->status, [self::INSPECTION_ACCEPTED, self::OFFER_SENT], true);
    }

    public function tab(): string
    {
        if ($this->isAwaitingResponse()) {
            return 'new';
        }
        if (in_array($this->status, [self::OFFER_SENT, self::INSPECTION_ACCEPTED], true)
            || ($this->status === self::OFFER_EXPIRED && $this->reconfirm_requested_at)) {
            return 'offer_sent';
        }
        if ($this->status === self::JOB_ACCEPTED) {
            return $this->isFinished() ? 'completed' : 'awarded';
        }

        return 'closed';
    }

    public function statusLabel(): string
    {
        if ($this->status === self::JOB_ACCEPTED && $this->job_status && $this->job_status !== self::JOB_ACCEPTED_STATE) {
            return match ($this->job_status) {
                self::JOB_SCHEDULED => 'Scheduled',
                self::JOB_IN_PROGRESS => 'In progress',
                self::JOB_COMPLETED => 'Completed',
                self::JOB_CONFIRMED => 'Confirmed',
                self::JOB_INVOICED => 'Invoiced',
                default => 'Job accepted',
            };
        }

        return match ($this->status) {
            self::INVITED => 'Invited',
            self::VIEWED => 'Viewed',
            self::DECLINED => 'Declined',
            self::WITHDRAWN => 'Withdrawn',
            self::EXPIRED => 'Closed – expired',
            self::OFFER_SENT => $this->revision > 0 ? 'Offer updated' : 'Offer sent',
            self::INSPECTION_ACCEPTED => 'Inspection accepted',
            self::OFFER_WITHDRAWN => 'Offer withdrawn',
            self::OFFER_EXPIRED => $this->reconfirm_requested_at ? 'Reconfirm requested' : 'Offer expired',
            self::NOT_SELECTED => 'Not selected',
            self::JOB_ACCEPTED => 'Job accepted',
            self::CLOSED => 'Closed',
            self::CANCELLED => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }

    public function pricingLabel(): string
    {
        return self::PRICING_TYPES[$this->pricing_type] ?? '—';
    }

    /** Earliest proposed visit, used for sorting offers. */
    public function earliestSlot(): ?\Illuminate\Support\Carbon
    {
        return collect($this->visit_slots ?? [])
            ->map(fn ($slot) => rescue(fn () => \Illuminate\Support\Carbon::parse($slot), null, false))
            ->filter()
            ->sort()
            ->first();
    }
}
