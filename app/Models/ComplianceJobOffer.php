<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceJobOffer extends Model
{
    public const INVITED = 'invited';

    public const VIEWED = 'viewed';

    public const DECLINED = 'declined';

    public const WITHDRAWN = 'withdrawn';

    public const EXPIRED = 'expired';

    public const OFFER_SENT = 'offer_sent';

    public const OFFER_EXPIRED = 'offer_expired';

    public const NOT_SELECTED = 'not_selected';

    public const JOB_ACCEPTED = 'job_accepted';

    public const AWAITING = [self::INVITED, self::VIEWED];

    public const OPEN_STATUSES = [self::INVITED, self::VIEWED, self::OFFER_SENT];

    public const BLOCKING = [self::INVITED, self::VIEWED, self::OFFER_SENT, self::JOB_ACCEPTED];

    public const QUOTABLE = [self::INVITED, self::VIEWED, self::OFFER_SENT];

    protected $fillable = [
        'account_id',
        'compliance_booking_id',
        'contractor_user_id',
        'invited_by',
        'status',
        'pricing_type',
        'vat_treatment',
        'vat_rate',
        'net_amount',
        'vat_amount',
        'gross_amount',
        'hourly_rate',
        'estimated_hours',
        'call_out_fee',
        'minimum_hours',
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
        'visit_slots',
        'invited_at',
        'viewed_at',
        'invite_expires_at',
        'offer_sent_at',
        'offer_expires_at',
        'decided_at',
        'scheduled_for',
    ];

    protected $casts = [
        'vat_rate' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'estimated_hours' => 'decimal:2',
        'call_out_fee' => 'decimal:2',
        'minimum_hours' => 'decimal:2',
        'price_cap' => 'decimal:2',
        'inspection_fee' => 'decimal:2',
        'materials_estimate' => 'decimal:2',
        'price_is_estimate' => 'boolean',
        'visit_slots' => 'array',
        'revision' => 'integer',
        'invited_at' => 'datetime',
        'viewed_at' => 'datetime',
        'invite_expires_at' => 'datetime',
        'offer_sent_at' => 'datetime',
        'offer_expires_at' => 'datetime',
        'decided_at' => 'datetime',
        'scheduled_for' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(ComplianceBooking::class, 'compliance_booking_id');
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contractor_user_id');
    }

    public function isAwaitingResponse(): bool
    {
        return in_array($this->status, self::AWAITING, true);
    }

    public function canQuote(): bool
    {
        return in_array($this->status, self::QUOTABLE, true);
    }

    public function showsAddress(): bool
    {
        return $this->status === self::JOB_ACCEPTED;
    }

    public function tab(): string
    {
        if ($this->isAwaitingResponse()) {
            return 'new';
        }
        if ($this->status === self::OFFER_SENT) {
            return 'offer_sent';
        }
        if ($this->status === self::JOB_ACCEPTED) {
            return 'awarded';
        }

        return 'closed';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::INVITED => 'Invited',
            self::VIEWED => 'Viewed',
            self::DECLINED => 'Declined',
            self::WITHDRAWN => 'Withdrawn',
            self::EXPIRED => 'Invite expired',
            self::OFFER_SENT => 'Offer sent',
            self::OFFER_EXPIRED => 'Offer expired',
            self::NOT_SELECTED => 'Not selected',
            self::JOB_ACCEPTED => 'Booked',
            default => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }

    public function contractorName(): string
    {
        return $this->contractor?->contractorProfile?->displayName()
            ?: ($this->contractor?->name ?: 'Contractor');
    }

    public function priceSummary(): string
    {
        $total = '£'.number_format((float) $this->gross_amount, 2);
        $vat = (float) $this->vat_amount > 0 ? ' inc VAT' : ' (no VAT)';

        return match ($this->pricing_type) {
            'hourly' => 'Estimated '.$total.$vat,
            'callout_hourly' => 'Estimated '.$total.$vat,
            default => 'Fixed '.$total.$vat,
        };
    }

    public function placeLine(): string
    {
        $property = $this->booking?->property;
        if (! $property) {
            return 'Area shared on the job';
        }
        if ($this->showsAddress()) {
            return trim(collect([$property->line_1, $property->city, $property->postcode])->filter()->implode(', ')) ?: 'Address on file';
        }

        return trim(collect([
            $property->city,
            \App\Support\ContractorCompliance::postcodeDistrict($property->postcode),
        ])->filter()->implode(' ')) ?: 'Area shared on the job';
    }
}
