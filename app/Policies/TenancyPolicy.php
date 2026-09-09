<?php

namespace App\Policies;

use App\Models\Tenancy;
use App\Models\User;
use App\Support\AccountMembership;
use App\Support\WorkspaceAccess;

class TenancyPolicy
{
    public function viewAny(User $user): bool
    {
        if (WorkspaceAccess::canManageCurrentWorkspace($user)) {
            return true;
        }

        $membership = current_account_membership($user);

        return $membership && AccountMembership::isTenant($membership->member_type);
    }

    public function view(User $user, Tenancy $tenancy): bool
    {
        if (WorkspaceAccess::canManageCurrentWorkspace($user)) {
            return WorkspaceAccess::belongsToCurrentAccount($tenancy);
        }

        return WorkspaceAccess::isTenantOnTenancy($user, $tenancy);
    }

    public function create(User $user): bool
    {
        return WorkspaceAccess::canManageCurrentWorkspace($user)
            && ! AccountMembership::isTenant(current_account_membership($user)?->member_type);
    }

    public function update(User $user, Tenancy $tenancy): bool
    {
        return $this->create($user)
            && WorkspaceAccess::belongsToCurrentAccount($tenancy);
    }

    public function delete(User $user, Tenancy $tenancy): bool
    {
        return $this->update($user, $tenancy);
    }

    public function restore(User $user, Tenancy $tenancy): bool
    {
        return false;
    }

    public function forceDelete(User $user, Tenancy $tenancy): bool
    {
        return false;
    }
}
