<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComplianceBooking extends Model
{
    public const DRAFT = 'draft';

    public const SENT = 'sent';

    public const BOOKED = 'booked';

    public const COMPLETED = 'completed';

    public const CANCELLED = 'cancelled';

    public const OPEN = [self::DRAFT, self::SENT, self::BOOKED];

    public const TRADES = [
        'gas' => 'gas',
        'eicr' => 'electrical',
        'epc' => 'energy',
    ];

    protected $fillable = [
        'account_id',
        'property_id',
        'compliance_type_id',
        'trade',
        'status',
        'awarded_offer_id',
        'access_notes',
        'respond_by',
        'sent_by',
        'first_sent_at',
        'last_sent_at',
        'awarded_at',
    ];

    protected $casts = [
        'respond_by' => 'datetime',
        'first_sent_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'awarded_at' => 'datetime',
    ];

    public static function tradeForAlias(string $alias): ?string
    {
        return self::TRADES[$alias] ?? null;
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ComplianceType::class, 'compliance_type_id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(ComplianceJobOffer::class);
    }

    public function awardedOffer(): BelongsTo
    {
        return $this->belongsTo(ComplianceJobOffer::class, 'awarded_offer_id');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN, true);
    }

    public function landlordStatus(): string
    {
        if ($this->status === self::DRAFT) {
            return 'Choose a contractor';
        }

        if ($this->status === self::COMPLETED) {
            $offer = $this->awardedContractorOffer();
            $name = $offer?->contractorName() ?: 'a contractor';
            if ($offer?->disputed_at) {
                return 'Disputed with '.$name;
            }
            if ($offer?->job_status === ComplianceJobOffer::JOB_INVOICED) {
                return 'Invoiced by '.$name;
            }

            return 'Completed with '.$name;
        }

        if ($this->status === self::BOOKED) {
            $offer = $this->awardedContractorOffer();
            $name = $offer?->contractorName() ?: 'a contractor';
            if ($offer?->disputed_at) {
                return 'Disputed with '.$name;
            }
            $when = $offer?->scheduled_for;
            if ($offer?->job_status === ComplianceJobOffer::JOB_COMPLETED) {
                return 'Completed by '.$name.'. Confirm the check';
            }
            if ($offer?->job_status === ComplianceJobOffer::JOB_IN_PROGRESS) {
                return 'In progress with '.$name;
            }

            return 'Booked with '.$name.($when ? ' on '.$when->format('j M, H:i') : '');
        }

        $offers = $this->relationLoaded('offers') ? $this->offers : $this->offers()->get();
        if ($offers->contains(fn (ComplianceJobOffer $offer) => $offer->status === ComplianceJobOffer::OFFER_SENT)) {
            return 'Quotes ready';
        }

        return 'Waiting for quotes';
    }

    public function awardedContractorOffer(): ?ComplianceJobOffer
    {
        return $this->relationLoaded('awardedOffer') ? $this->awardedOffer : $this->awardedOffer()->with('contractor.contractorProfile')->first();
    }

    public function landlordUrl(): string
    {
        if ($this->status === self::DRAFT) {
            return route('admin.compliance.bookings.find', $this);
        }

        return route('admin.compliance.bookings.show', $this);
    }
}
