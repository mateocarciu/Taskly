<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view user models, only for owners and admins.
     */
    public function viewAny(User $user): bool
    {
        return $user->isPrivileged();
    }

    /**
     * Determine whether the user can create user models, only for owners and admins.
     */
    public function create(User $user): bool
    {
        return $user->isPrivileged();
    }

    /**
     * Determine whether the user can remove user models from the team, only for owners and admins.
     */
    public function delete(User $user, User $target): bool
    {
        if (! $user->isOwner()) {
            return false;
        }

        return $user->id !== $target->id && ! $target->isOwner();
    }

    /**
     * Determine whether the user can transfer ownership of user models, only for owners.
     */
    public function transferOwnership(User $user): bool
    {
        return $user->isOwner();
    }
}
