<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    /**
     * Determine whether the user can view the event details.
     */
    public function view(User $user, Event $event): bool
    {
        return $event->calendar?->team_id !== null
            && $user->canAccessTeam($event->calendar->team_id);
    }

    /**
     * Determine whether the user can update the event.
     */
    public function update(User $user, Event $event): bool
    {
        return $user->id === $event->organizer_id;
    }

    /**
     * Determine whether the user can remove the event from their calendar.
     */
    public function delete(User $user, Event $event): bool
    {
        return $event->calendar?->team_id !== null
            && $user->canAccessTeam($event->calendar->team_id);
    }

    /**
     * Determine whether the user can answer the event invitation.
     */
    public function respond(User $user, Event $event): bool
    {
        return $event->calendar?->team_id !== null
            && $user->canAccessTeam($event->calendar->team_id);
    }

    /**
     * Determine whether the user can join the event's video room.
     */
    public function room(User $user, Event $event): bool
    {
        return $event->calendar?->team_id !== null
            && $user->canAccessTeam($event->calendar->team_id)
            && $event->has_video;
    }
}
