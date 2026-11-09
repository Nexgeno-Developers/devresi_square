<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceJobPayment extends Model
{
    public const PENDING = 'pending';

    public const PAID = 'paid';

    public const PAYOUT_UNPAID = 'unpaid';

    public const PAYOUT_PAID = 'paid';

    public const BEARER_CONTRACTOR = 'contractor';

    public const BEARER_LANDLORD = 'landlord';

    protected $fillable = [
        'compliance_job_invoice_id',
        'account_id',
        'paid_by',
        'gross_amount',
        'commission_percent',
        'commission_bearer',
        'commission_amount',
        'landlord_amount',
        'contractor_amount',
        'currency',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'status',
        'paid_at',
        'payout_status',
        'payout_reference',
        'payout_paid_at',
        'payout_paid_by',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'commission_percent' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'landlord_amount' => 'decimal:2',
        'contractor_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'payout_paid_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ComplianceJobInvoice::class, 'compliance_job_invoice_id');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function payoutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payout_paid_by');
    }

    public function isPaid(): bool
    {
        return $this->status === self::PAID;
    }

    public function payoutIsSent(): bool
    {
        return $this->payout_status === self::PAYOUT_PAID;
    }
}
