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
            return 'contractor.jobs.index';
        }

        if ($user->hasRole('Contractor') && ! $user->hasRole('Tenant')) {
            return 'contractor.jobs.index';
        }

        if ($user->hasRole('Tenant')) {
            return 'backend.home';
        }

        return 'backend.dashboard';
    }

    public static function to(User $user)
    {
        return redirect()->route(self::routeName($user));
    }

    public const ACCESS_REMOVED = 'Your access to this portal has been removed. Ask your landlord to invite you again.';

    /**
     * A portal user whose every account membership was revoked has nowhere to
     * go, so sign-in is refused instead of opening a session on a 403 page.
     * Contractors work without an account membership and are left alone.
     */
    public static function accessRemoved(User $user): bool
    {
        if ($user->isSuperAdmin() || $user->hasRole('Contractor')) {
            return false;
        }

        if (! $user->accountUsers()->exists()) {
            return false;
        }

        return ! $user->accountUsers()->where('status', 'active')->where('can_login', true)->exists();
    }
}
