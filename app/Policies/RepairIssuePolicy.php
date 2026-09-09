<?php

namespace App\Policies;

use App\Models\RepairIssue;
use App\Models\User;
use App\Support\AccountMembership;
use App\Support\WorkspaceAccess;

class RepairIssuePolicy
{
    public function viewAny(User $user): bool
    {
        return WorkspaceAccess::canManageCurrentWorkspace($user)
            || AccountMembership::isTenant(current_account_membership($user)?->member_type);
    }

    public function view(User $user, RepairIssue $repairIssue): bool
    {
        if (! WorkspaceAccess::belongsToCurrentAccount($repairIssue)) {
            return false;
        }

        if (WorkspaceAccess::canManageCurrentWorkspace($user)) {
            return true;
        }

        return (int) $repairIssue->tenant_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, RepairIssue $repairIssue): bool
    {
        return WorkspaceAccess::canManageCurrentWorkspace($user)
            && WorkspaceAccess::belongsToCurrentAccount($repairIssue);
    }

    public function delete(User $user, RepairIssue $repairIssue): bool
    {
        return $this->update($user, $repairIssue);
    }
}
