<?php

namespace App\Policies;

use App\Models\Column;
use App\Models\User;

class ColumnPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasActiveTeam();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Column $column): bool
    {
        return $user->canAccessTeam($column->team_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasActiveTeam();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Column $column): bool
    {
        return $user->isPrivileged() && $user->canAccessTeam($column->team_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Column $column): bool
    {
        return $user->isPrivileged() && $user->canAccessTeam($column->team_id);
    }

    /**
     * Determine whether the user can update the sequence of the model.
     */
    public function updateSequence(User $user, Column $column): bool
    {
        return $user->isPrivileged() && $user->canAccessTeam($column->team_id);
    }
}
