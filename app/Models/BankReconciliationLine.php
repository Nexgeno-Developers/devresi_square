<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankReconciliationLine extends Model
{
    protected $fillable = [
        'bank_reconciliation_id', 'gl_journal_line_id',
        'date', 'description', 'amount', 'is_matched',
    ];

    protected $casts = [
        'amount' => 'float',
        'is_matched' => 'boolean',
    ];

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class, 'bank_reconciliation_id');
    }

    public function journalLine(): BelongsTo
    {
        return $this->belongsTo(GlJournalLine::class, 'gl_journal_line_id');
    }
}
