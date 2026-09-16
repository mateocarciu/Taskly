<?php

namespace App\Observers;

use App\Models\Calendar;
use App\Models\TeamMembership;

class TeamMembershipObserver
{
    /**
     * Ensure each team member owns a personal calendar for the team.
     */
    public function created(TeamMembership $membership): void
    {
        Calendar::firstOrCreate(
            ['team_id' => $membership->team_id, 'owner_id' => $membership->user_id],
            ['name' => 'My Calendar', 'color' => null],
        );
    }
}
