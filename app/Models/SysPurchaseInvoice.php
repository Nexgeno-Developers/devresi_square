<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany as MorphManyRelation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\GlJournal;

class SysPurchaseInvoice extends Model
{
    use HasFactory;

    protected $table = 'sys_purchase_invoices';
    protected $guarded = [];
    const UPDATED_AT = null;

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

    public function activeJournal(): ?GlJournal
    {
        $journal = GlJournal::activeFor('purchase_invoice_issue', $this->id);
        if ($journal) {
            return $journal;
        }
        return GlJournal::where('source_type', 'purchase_invoice')
            ->where('source_id', $this->id)
            ->whereNull('reversal_of_id')
            ->whereDoesntHave('reversal')
            ->where('memo', 'like', 'Post purchase invoice%')
            ->orderByDesc('id')
            ->first();
    }

    public function hasActiveJournal(): bool
    {
        return (bool) $this->activeJournal();
    }
}
