<?php

namespace App\Models;

use App\Traits\BelongsToSaasAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentPayment extends Model
{
    use BelongsToSaasAccount;

    public const METHODS = [
        'bank_transfer' => 'Bank transfer',
        'cash' => 'Cash',
        'other' => 'Other',
    ];

    protected $fillable = [
        'account_id',
        'rent_invoice_id',
        'amount',
        'paid_at',
        'method',
        'reference',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'paid_at' => 'date',
        'amount' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(RentInvoice::class, 'rent_invoice_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
