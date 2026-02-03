<?php

namespace App\Policies;

use App\Models\Tenancy;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TenancyPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin','Property Manager','Landlord']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Tenancy $tenancy): bool
    {
        if ($user->hasAnyRole(['Super Admin','Property Manager'])) {
            return true;
        }
        // Landlord sees tenancies only on their properties
        return $user->hasRole('Landlord') && $tenancy->property->created_by === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin','Property Manager']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Tenancy $tenancy): bool
    {
        return $user->hasAnyRole(['Super Admin','Property Manager']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Tenancy $tenancy): bool
    {
        return $user->hasAnyRole(['Super Admin','Property Manager']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Tenancy $tenancy): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Tenancy $tenancy): bool
    {
        return false;
    }
}
