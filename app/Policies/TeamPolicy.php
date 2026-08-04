<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    /**
     * Owner or admin can create teams.
     */
    public function create(User $user): bool
    {
        return $user->isPrivileged();
    }

    /**
     * Owner or admin can rename any team.
     */
    public function rename(User $user, Team $team): bool
    {
        return $user->isPrivileged();
    }

    /**
     * Owner can delete the team.
     */
    public function delete(User $user, Team $team): bool
    {
        return $user->isOwner();
    }

    /**
     * Owner or admin can add a member to the team.
     */
    public function addMember(User $user, Team $team): bool
    {
        return $user->isPrivileged();
    }

    /**
     * Only plain members can be removed from a team.
     * Privileged users (owner/admin) always have access, so they cannot be removed.
     */
    public function removeMember(User $user, Team $team, User $target): bool
    {
        if ($target->isPrivileged() || $user->id === $target->id) {
            return false;
        }

        return $user->isPrivileged();
    }

    /**
     * Owner or admin can promote a plain member to admin.
     * Cannot promote an already-privileged user.
     */
    public function promote(User $user, Team $team, User $target): bool
    {
        return $user->isPrivileged() && $target->role === 'member';
    }

    /**
     * Owner can demote any member.
     * Admin can demote members but NOT other admins or the owner.
     */
    public function demote(User $user, Team $team, User $target): bool
    {
        return $user->isOwner() && ! $target->isOwner();
    }
    
    /**
     * Everyone can switch to a team they have access to.
     */
    public function switch(User $user, Team $team): bool
    {
        return $user->canAccessTeam($team);
    }
}
