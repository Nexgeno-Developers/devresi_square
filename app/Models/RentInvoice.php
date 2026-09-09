<?php

namespace App\Models;

use App\Traits\BelongsToSaasAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentInvoice extends Model
{
    use BelongsToSaasAccount;

    public const STATUS_ISSUED = 'issued';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_PAID = 'paid';
    public const STATUS_VOID = 'void';

    protected $fillable = [
        'account_id',
        'property_id',
        'tenancy_id',
        'tenant_user_id',
        'invoice_no',
        'issue_date',
        'due_date',
        'period_start',
        'period_end',
        'amount',
        'balance',
        'status',
        'note',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'amount' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function tenancy(): BelongsTo
    {
        return $this->belongsTo(Tenancy::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_user_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(RentPayment::class)->orderByDesc('paid_at')->orderByDesc('id');
    }

    public function getInvoiceDateAttribute()
    {
        return $this->issue_date;
    }

    public function getTotalAmountAttribute()
    {
        return $this->amount;
    }

    public function getBalanceAmountAttribute()
    {
        return $this->balance;
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_ISSUED, self::STATUS_PARTIAL], true)
            && (float) $this->balance > 0;
    }

    public function canVoid(): bool
    {
        $hasPayments = $this->relationLoaded('payments')
            ? $this->payments->isNotEmpty()
            : $this->payments()->exists();

        return $this->status === self::STATUS_ISSUED && ! $hasPayments;
    }
}
