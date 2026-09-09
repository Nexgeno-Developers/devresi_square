<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\AccountUser;
use App\Models\User;
use App\Support\AccountMembership;

class BillingPolicy
{
    public function view(User $user, ?Account $account = null): bool
    {
        return $this->manage($user, $account);
    }

    public function manage(User $user, ?Account $account = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $accountId = $account?->id ?? current_account_id();
        $account ??= $accountId ? Account::query()->find($accountId) : null;

        if (! $account) {
            return false;
        }

        $membership = AccountUser::query()
            ->where('account_id', $account->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        return $membership
            && $membership->can_login
            && (int) $account->owner_user_id === (int) $user->id
            && AccountMembership::isWorkspaceAdmin($membership->member_type);
    }
}
