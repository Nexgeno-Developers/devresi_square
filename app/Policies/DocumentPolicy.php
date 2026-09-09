<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use App\Support\AccountMembership;
use App\Support\WorkspaceAccess;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return WorkspaceAccess::canManageCurrentWorkspace($user)
            || AccountMembership::isTenant(current_account_membership($user)?->member_type);
    }

    public function view(User $user, Document $document): bool
    {
        if (! WorkspaceAccess::belongsToCurrentAccount($document)) {
            return false;
        }

        if (WorkspaceAccess::canManageCurrentWorkspace($user)) {
            return true;
        }

        return AccountMembership::isTenant(current_account_membership($user)?->member_type)
            && $document->isSharedWithTenant();
    }

    public function download(User $user, Document $document): bool
    {
        return $this->view($user, $document);
    }

    public function share(User $user, Document $document): bool
    {
        return $this->update($user, $document);
    }

    public function create(User $user): bool
    {
        return WorkspaceAccess::canManageCurrentWorkspace($user);
    }

    public function update(User $user, Document $document): bool
    {
        return $this->create($user) && WorkspaceAccess::belongsToCurrentAccount($document);
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->update($user, $document);
    }
}
