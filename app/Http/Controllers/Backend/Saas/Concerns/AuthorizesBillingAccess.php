<?php

namespace App\Http\Controllers\Backend\Saas\Concerns;

use App\Models\Account;
use App\Models\AccountUser;
use App\Services\Saas\CurrentAccountService;
use App\Support\AccountMembership;
use App\Support\AccountType;
use Illuminate\Http\Request;

trait AuthorizesBillingAccess
{
    protected function billingAccount(Request $request): Account
    {
        $user = $request->user();
        abort_unless($user, 403);

        $account = app(CurrentAccountService::class)->current($user);
        abort_unless($account, 403, 'No current SaaS account is selected.');

        if ($user->hasRole('Super Admin')) {
            return $account;
        }

        $membership = AccountUser::query()
            ->where('account_id', $account->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        abort_unless($this->canAccessBilling($user, $account, $membership), 403, 'You do not have access to billing.');

        return $account;
    }

    protected function canAccessBilling($user, Account $account, ?AccountUser $membership): bool
    {
        if (! $membership || ! $membership->can_login) {
            return false;
        }

        if (
            AccountType::isLandlord($account->account_type)
            && in_array($membership->member_type, [
                AccountMembership::OWNER,
                AccountMembership::ADMIN,
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
