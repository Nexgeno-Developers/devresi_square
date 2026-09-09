<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;
use App\Services\Saas\PortalAccessService;
use App\Support\WorkspaceAccess;

class PropertyPolicy
{
    public function viewAny(User $user): bool
    {
        return WorkspaceAccess::canManageCurrentWorkspace($user)
            || $user->hasRole('Landlord');
    }

    public function view(User $user, Property $property): bool
    {
        return app(PortalAccessService::class)->canAccessProperty($user, $property, 'view');
    }

    public function create(User $user): bool
    {
        if (is_landlord_plan_user($user)) {
            return true;
        }

        return WorkspaceAccess::canManageCurrentWorkspace($user)
            && $user->can('create properties');
    }

    public function update(User $user, Property $property): bool
    {
        if (! WorkspaceAccess::belongsToCurrentAccount($property)) {
            return false;
        }

        if (is_landlord_plan_user($user)) {
            return true;
        }

        return app(PortalAccessService::class)->canAccessProperty($user, $property, 'edit');
    }

    public function delete(User $user, Property $property): bool
    {
        return $user->isSuperAdmin()
            && WorkspaceAccess::belongsToCurrentAccount($property);
    }

    public function restore(User $user, Property $property): bool
    {
        return $this->update($user, $property);
    }

    public function forceDelete(User $user, Property $property): bool
    {
        return $this->delete($user, $property);
    }
}
