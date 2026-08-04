<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;

class TagPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasActiveTeam();
    }

    /**
     * Determine whether the user can update the tag.
     */
    public function update(User $user, Tag $tag): bool
    {
        return $user->canAccessTeam($tag->team_id);
    }

    /**
     * Determine whether the user can delete the tag.
     */
    public function delete(User $user, Tag $tag): bool
    {
        return $user->canAccessTeam($tag->team_id);
    }
}
