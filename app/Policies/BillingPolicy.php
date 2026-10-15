<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\AccountUser;
use App\Models\User;
use App\Support\AccountMembership;
use App\Support\AccountType;

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

        if (! $membership || ! $membership->can_login) {
            return false;
        }

        if (
            AccountType::isLandlord($account->account_type)
            && in_array($membership->member_type, [
                AccountMembership::STAFF,
                AccountMembership::PROPERTY_MANAGER,
                AccountMembership::LANDLORD_CONTACT,
            ], true)
        ) {
            return true;
        }

        return (int) $account->owner_user_id === (int) $user->id
            && AccountMembership::isWorkspaceAdmin($membership->member_type);
    }
}
