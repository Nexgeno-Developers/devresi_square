<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankReconciliation extends Model
{
    protected $fillable = [
        'sys_bank_account_id', 'statement_date', 'statement_balance',
        'gl_balance', 'difference', 'status', 'reconciled_by',
        'reconciled_at', 'notes',
    ];

    protected $casts = [
        'statement_balance' => 'float',
        'gl_balance' => 'float',
        'difference' => 'float',
        'reconciled_at' => 'datetime',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(SysBankAccount::class, 'sys_bank_account_id');
    }

    public function reconciledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BankReconciliationLine::class);
    }
}
