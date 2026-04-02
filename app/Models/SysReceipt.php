<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;
use App\Models\GlJournal;

class SysReceipt extends Model
{
    use HasFactory;

    protected $table = 'sys_receipts';
    protected $guarded = [];
    const UPDATED_AT = null;
    protected $casts = [
        'amount' => 'float',
        'applied_amount' => 'float',
        'payment_meta' => 'array',
    ];

    public function receiptable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(GlJournal::class, 'gl_journal_id');
    }

    /** Remaining amount on this receipt/credit */
    public function getRemainingAmountAttribute(): float
    {
        $amount = (float) ($this->amount ?? 0);
        $applied = (float) ($this->applied_amount ?? 0);
        return max(0, $amount - $applied);
    }

    /** Scope: customer advance credits (unapplied/partially_applied) */
    public function scopeCustomerCredits(Builder $query, int $userId): Builder
    {
        return $query->where('receiptable_type', 'user')
            ->where('receiptable_id', $userId)
            ->whereIn('status', ['unapplied', 'partially_applied']);
    }
}
