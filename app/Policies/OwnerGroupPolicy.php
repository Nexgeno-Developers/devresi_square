<?php

namespace App\Policies;

use App\Models\OwnerGroup;
use App\Models\User;
use App\Services\Saas\PortalAccessService;
use App\Support\WorkspaceAccess;

class OwnerGroupPolicy
{
    public function viewAny(User $user): bool
    {
        return WorkspaceAccess::canManageCurrentWorkspace($user);
    }

    public function view(User $user, OwnerGroup $ownerGroup): bool
    {
        $property = $ownerGroup->property;

        if (! $property) {
            return false;
        }

        return app(PortalAccessService::class)->canAccessProperty($user, $property, 'view');
    }

    public function create(User $user): bool
    {
        return WorkspaceAccess::canManageCurrentWorkspace($user);
    }

    public function update(User $user, OwnerGroup $ownerGroup): bool
    {
        return $this->create($user) && $this->view($user, $ownerGroup);
    }

    public function delete(User $user, OwnerGroup $ownerGroup): bool
    {
        return $this->update($user, $ownerGroup);
    }
}
