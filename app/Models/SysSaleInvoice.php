<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany as MorphManyRelation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\GlJournal;

class SysSaleInvoice extends Model
{
    use HasFactory;

    public const LINK_TO_TYPES = ['Property', 'Tenancy', 'Contractor'];
    public const CHARGE_TO_TYPES = ['Owner', 'Tenant', 'Contractor'];

    protected $table = 'sys_sale_invoices';
    protected $guarded = [];
    const UPDATED_AT = null;
    protected $casts = [
        'link_to_id' => 'integer',
        'charge_to_id' => 'integer',
        'bank_account_id' => 'integer',
        'recurring_master_invoice_id' => 'integer',
        'recurring_sequence' => 'integer',
        'recurring_month_interval' => 'integer',
        'recurring_custom_interval' => 'integer',
        'unlimited_cycles' => 'boolean',
        'recurring_cycles' => 'integer',

        'penalty_enabled' => 'boolean',
        'penalty_fixed_rate' => 'decimal:2',
        'penalty_amount_input' => 'decimal:2',
        'penalty_grace_days' => 'integer',
        'penalty_max_amount' => 'decimal:2',
        'penalty_amount_applied' => 'decimal:2',
        'penalty_applied_at' => 'datetime',
        'penalty_gl_account_id' => 'integer',
    ];

    public function receipts(): MorphMany
    {
        return $this->morphMany(SysReceipt::class, 'receiptable');
    }

    public function payments(): MorphManyRelation
    {
        return $this->morphMany(SysPayment::class, 'reference', 'reference_type', 'reference_id')->latest('id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function invoiceHeader(): BelongsTo
    {
        return $this->belongsTo(SysInvoiceHeader::class, 'invoice_header_id');
    }

    public function linkTo(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'link_to_type', 'link_to_id');
    }

    public function chargeTo(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'charge_to_type', 'charge_to_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function getCustomerAvailableCreditAttribute(): float
    {
        return $this->user?->available_credit ?? 0;
    }

    public function journals(): MorphMany
    {
        return $this->morphMany(GlJournal::class, 'source');
    }

    public function activeJournal(): ?GlJournal
    {
        // Prefer the newer dedicated issue type
        $journal = GlJournal::activeFor('sale_invoice_issue', $this->id);
        if ($journal) {
            return $journal;
        }
        // Fallback for legacy journals stored with generic sale_invoice type
        return GlJournal::where('source_type', 'sale_invoice')
            ->where('source_id', $this->id)
            ->whereNull('reversal_of_id')
            ->whereDoesntHave('reversal')
            ->where('memo', 'like', 'Issue invoice%')
            ->orderByDesc('id')
            ->first();
    }

    public function hasActiveJournal(): bool
    {
        return (bool) $this->activeJournal();
    }

    public function items(): HasMany
    {
        return $this->hasMany(SysSaleInvoiceItem::class, 'sale_invoice_id');
    }

    protected static function booted(): void
    {
        static::deleting(function (SysSaleInvoice $invoice) {
            $invoice->items()->delete();
        });
    }
}
