<?php

namespace App\Http\Controllers\Backend\Saas\Concerns;

use App\Models\Account;
use App\Models\AccountUser;
use App\Services\Saas\CurrentAccountService;
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

        abort_unless($this->canAccessBilling($membership), 403, 'You do not have access to billing.');

        return $account;
    }

    protected function canAccessBilling(?AccountUser $membership): bool
    {
        if (! $membership || ! $membership->can_login) {
            return false;
        }

        return in_array($membership->member_type, ['owner', 'admin'], true)
            && $membership->access_level === 'full';
    }
}
