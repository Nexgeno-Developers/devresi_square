<?php

namespace App\Support;

use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Models\User;

final class WorkspaceAccess
{
    public static function canManageCurrentWorkspace(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (is_landlord_plan_user($user)) {
            return true;
        }

        $membership = current_account_membership($user);

        if ($membership && AccountMembership::isWorkspaceAdmin($membership->member_type)) {
            return true;
        }

        if ($membership && $membership->member_type === AccountMembership::STAFF) {
            return true;
        }

        return $user->hasAnyRole(['Property Manager', 'Estate Agent', 'Owner', 'Staff']);
    }

    public static function belongsToCurrentAccount(?object $model): bool
    {
        $accountId = current_account_id();

        if (! $accountId || ! $model || ! isset($model->account_id)) {
            return false;
        }

        return (int) $model->account_id === (int) $accountId;
    }

    public static function isTenantOnTenancy(User $user, Tenancy $tenancy): bool
    {
        if (! self::belongsToCurrentAccount($tenancy)) {
            return false;
        }

        return TenantMember::query()
            ->where('tenancy_id', $tenancy->id)
            ->where('user_id', $user->id)
            ->exists();
    }
}
