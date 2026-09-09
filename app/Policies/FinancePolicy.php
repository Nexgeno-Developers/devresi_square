<?php

namespace App\Policies;

use App\Models\RentInvoice;
use App\Models\User;
use App\Support\AccountMembership;
use App\Support\WorkspaceAccess;

class FinancePolicy
{
    public function viewAny(User $user): bool
    {
        return WorkspaceAccess::canManageCurrentWorkspace($user)
            && ! AccountMembership::isTenant(current_account_membership($user)?->member_type);
    }

    public function view(User $user, RentInvoice $invoice): bool
    {
        if (! WorkspaceAccess::belongsToCurrentAccount($invoice)) {
            return false;
        }

        if ($this->viewAny($user)) {
            return true;
        }

        return AccountMembership::isTenant(current_account_membership($user)?->member_type)
            && (int) $invoice->tenant_user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, RentInvoice $invoice): bool
    {
        return $this->create($user) && WorkspaceAccess::belongsToCurrentAccount($invoice);
    }
}
