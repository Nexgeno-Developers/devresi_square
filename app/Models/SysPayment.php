<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\GlJournal;
use App\Models\SysReceipt;

class SysPayment extends Model
{
    use HasFactory;

    protected $table = 'sys_payments';
    protected $guarded = [];
    const UPDATED_AT = null;
    protected $casts = [
        'is_voided' => 'boolean',
        'amount' => 'float',
        'payment_meta' => 'array',
    ];

    public function reference(): MorphTo
    {
        // Polymorphic link to sale or purchase invoice
        return $this->morphTo(__FUNCTION__, 'reference_type', 'reference_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(SysBankAccount::class, 'sys_bank_account_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(GlJournal::class, 'gl_journal_id');
    }

    public function sourceReceipt(): BelongsTo
    {
        return $this->belongsTo(SysReceipt::class, 'source_receipt_id');
    }

    public function scopeNotVoided($query)
    {
        return $query->where('is_voided', false);
    }
}
