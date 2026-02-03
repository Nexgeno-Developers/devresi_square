<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Who can list users?
        return $user->hasAnyRole([
            'Super Admin','Property Manager','Estate Agent','Landlord'
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        if ($user->hasAnyRole(['Super Admin','Property Manager'])) {
            return true;
        }

        // Landlord: only users they created
        if ($user->hasRole('Landlord')) {
            return $model->created_by === $user->id;
        }

        // Estate Agent: only users in the same branch
        if ($user->hasRole('Estate Agent')) {
            return $model->branch_id === $user->branch_id;
        }

        // Everyone else only sees their own profile
        return $model->id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Allow editing own profile + managers/admins
        return $user->id === $model->id || $user->hasAnyRole(['Super Admin','Property Manager']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Only Super Admin can delete users
        return $user->hasRole('Super Admin');
        // return false; // IGNORE
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }
}
