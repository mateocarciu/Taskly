<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
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
    public function view(User $user, Task $task): bool
    {
        return $user->canAccessTeam($task->team_id);
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
    public function update(User $user, Task $task): bool
    {
        return $user->canAccessTeam($task->team_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Task $task): bool
    {
        return $user->canAccessTeam($task->team_id);
    }

    /**
     * Determine whether the user can comment on the model.
     */
    public function comment(User $user, Task $task): bool
    {
        return $user->canAccessTeam($task->team_id);
    }

    /**
     * Determine whether the user can update the sequence of the model.
     */
    public function updateSequence(User $user, Task $task): bool
    {
        return $user->canAccessTeam($task->team_id);
    }
}
