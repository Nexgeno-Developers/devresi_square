<?php

namespace App\Services\Saas;

use App\Models\Account;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class CurrentAccountService
{
    private const SESSION_KEY = 'current_account_id';

    public function current(?User $user = null): ?Account
    {
        $user ??= Auth::user();

        if (! $user) {
            return null;
        }

        $sessionAccountId = $this->sessionAccountId();

        if ($user->isSuperAdmin()) {
            if (! $sessionAccountId) {
                return null;
            }

            $account = Account::find($sessionAccountId);
            if (! $account) {
                $this->clearCurrentAccount();
            }

            return $account;
        }

        if ($sessionAccountId && $this->userCanAccessAccount($user, $sessionAccountId)) {
            return Account::find($sessionAccountId);
        }

        if ($sessionAccountId) {
            $this->clearCurrentAccount();
        }

        $membership = $user->accountUsers()
            ->where('status', 'active')
            ->with('account')
            ->orderBy('id')
            ->first();

        if (! $membership?->account) {
            return null;
        }

        $this->rememberAccount($user, (int) $membership->account_id);

        return $membership->account;
    }

    public function currentId(?User $user = null): ?int
    {
        return $this->current($user)?->id;
    }

    public function setCurrentAccount(User $user, int $accountId): Account
    {
        if ($user->isSuperAdmin()) {
            $account = Account::find($accountId);
            if (! $account) {
                throw new AuthorizationException('Selected account does not exist.');
            }

            $this->rememberAccount($user, $account->id);

            return $account;
        }

        $membership = $user->accountUsers()
            ->where('account_id', $accountId)
            ->where('status', 'active')
            ->with('account')
            ->first();

        if (! $membership?->account) {
            throw new AuthorizationException('Selected account is not available for this user.');
        }

        $this->rememberAccount($user, $accountId);

        return $membership->account;
    }

    public function availableAccounts(User $user): Collection
    {
        if ($user->isSuperAdmin()) {
            return Account::query()->orderBy('account_name')->orderBy('id')->get();
        }

        return Account::query()
            ->whereHas('accountUsers', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('status', 'active');
            })
            ->orderBy('account_name')
            ->orderBy('id')
            ->get();
    }

    public function userCanAccessAccount(User $user, int $accountId): bool
    {
        if ($user->isSuperAdmin()) {
            return Account::whereKey($accountId)->exists();
        }

        return $this->userHasActiveMembership($user, $accountId);
    }

    public function clearCurrentAccount(): void
    {
        if (app()->bound('session')) {
            session()->forget(self::SESSION_KEY);
        }
    }

    private function userHasActiveMembership(User $user, int $accountId): bool
    {
        return $user->accountUsers()
            ->where('account_id', $accountId)
            ->where('status', 'active')
            ->exists();
    }

    private function rememberAccount(User $user, int $accountId): void
    {
        if (app()->bound('session')) {
            session([self::SESSION_KEY => $accountId]);
        }

        if (Schema::hasColumn('users', 'last_active_account_id')) {
            $user->forceFill(['last_active_account_id' => $accountId])->saveQuietly();
        }
    }

    private function sessionAccountId(): ?int
    {
        if (! app()->bound('session')) {
            return null;
        }

        $accountId = session(self::SESSION_KEY);

        return is_numeric($accountId) ? (int) $accountId : null;
    }
}
