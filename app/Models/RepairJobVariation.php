<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairJobVariation extends Model
{
    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const DECLINED = 'declined';

    public const WITHDRAWN = 'withdrawn';

    protected $fillable = [
        'repair_job_offer_id', 'requested_by', 'reason', 'net_amount', 'vat_rate', 'vat_amount', 'gross_amount',
        'status', 'decided_by', 'decided_at', 'decision_note',
    ];

    protected $casts = [
        'net_amount' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'decided_at' => 'datetime',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(RepairJobOffer::class, 'repair_job_offer_id');
    }

    public function statusLabel(): string
    {
        return [
            self::PENDING => 'Waiting for the landlord',
            self::APPROVED => 'Approved',
            self::DECLINED => 'Declined',
            self::WITHDRAWN => 'Withdrawn',
        ][$this->status] ?? ucfirst((string) $this->status);
    }
}
