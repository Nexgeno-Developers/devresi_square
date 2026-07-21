<?php

namespace App\Services\Saas;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AccountScopedQueryService
{
    public function apply($query, ?User $user = null)
    {
        $user ??= Auth::user();

        if (! $user || $user->hasRole('Super Admin')) {
            return $query;
        }

        $accountId = app(CurrentAccountService::class)->currentId($user);

        if (! $accountId) {
            return $query instanceof Builder
                ? $query->whereRaw('1 = 0')
                : $query->whereRaw('1 = 0');
        }

        $table = method_exists($query, 'getModel')
            ? $query->getModel()->getTable()
            : null;

        return $table
            ? $query->where($table . '.account_id', $accountId)
            : $query->where('account_id', $accountId);
    }
}
