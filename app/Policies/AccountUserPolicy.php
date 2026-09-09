<?php

namespace App\Policies;

use App\Models\AccountUser;
use App\Models\User;
use App\Support\WorkspaceAccess;

class AccountUserPolicy
{
    public function viewAny(User $user): bool
    {
        return WorkspaceAccess::canManageCurrentWorkspace($user);
    }

    public function view(User $user, AccountUser $accountUser): bool
    {
        if ((int) $accountUser->user_id === (int) $user->id) {
            return (int) $accountUser->account_id === (int) current_account_id();
        }

        return WorkspaceAccess::canManageCurrentWorkspace($user)
            && (int) $accountUser->account_id === (int) current_account_id();
    }

    public function create(User $user): bool
    {
        return WorkspaceAccess::canManageCurrentWorkspace($user);
    }

    public function update(User $user, AccountUser $accountUser): bool
    {
        return $this->create($user)
            && (int) $accountUser->account_id === (int) current_account_id();
    }

    public function delete(User $user, AccountUser $accountUser): bool
    {
        return $this->update($user, $accountUser)
            && (int) $accountUser->user_id !== (int) $user->id;
    }
}
