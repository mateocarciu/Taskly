<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Tag;

class TagPolicy
{
    /**
     * Determine whether the user can update the tag.
     */
    public function update(User $user, Tag $tag): bool
    {
        return $user->team_id === $tag->team_id;
    }

    /**
     * Determine whether the user can delete the tag.
     */
    public function delete(User $user, Tag $tag): bool
    {
        return $user->team_id === $tag->team_id;
    }
}
