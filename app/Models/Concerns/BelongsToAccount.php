<?php

namespace App\Models\Concerns;

use App\Models\Account;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToAccount
{
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function scopeForAccount(Builder $query, int|string|null $accountId): Builder
    {
        if ($accountId === null || $accountId === '') {
            return $query->whereRaw('1 = 0');
        }

        return $query->where($this->getTable() . '.account_id', $accountId);
    }
}
