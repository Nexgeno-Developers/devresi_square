<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PropertyPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Super Admin & Property Manager see all,
        // Landlord sees only their own properties
        return $user->hasAnyRole(['Super Admin','Property Manager','Landlord']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Property $property): bool
    {
        if ($user->hasAnyRole(['Super Admin','Property Manager'])) {
            return true;
        }
        // Landlord can view only properties they created
        return $user->hasRole('Landlord') && $property->created_by === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only Super Admin & Property Manager can create new
        return $user->hasAnyRole(['Super Admin','Property Manager']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Property $property): bool
    {
        // Only Super Admin & Property Manager
        return $user->hasAnyRole(['Super Admin','Property Manager']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Property $property): bool
    {
        // Only Super Admin
        return $user->hasRole('Super Admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Property $property): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Property $property): bool
    {
        return false;
    }
}
