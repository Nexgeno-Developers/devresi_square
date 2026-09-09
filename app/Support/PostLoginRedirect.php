<?php

namespace App\Support;

use App\Models\User;

final class PostLoginRedirect
{
    public static function routeName(User $user): string
    {
        if ($user->isSuperAdmin()) {
            return 'backend.saas.accounts.index';
        }

        $membership = current_account_membership($user);

        if ($membership && AccountMembership::isTenant($membership->member_type)) {
            return 'backend.home';
        }

        if ($membership && $membership->member_type === AccountMembership::CONTRACTOR) {
            return 'backend.home';
        }

        if ($user->hasRole('Tenant') || $user->hasRole('Contractor')) {
            return 'backend.home';
        }

        return 'backend.dashboard';
    }

    public static function to(User $user)
    {
        return redirect()->route(self::routeName($user));
    }
}
