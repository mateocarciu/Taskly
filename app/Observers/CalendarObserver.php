<?php

namespace App\Observers;

use App\Models\Calendar;

class CalendarObserver
{
    /**
     * Add the owner to the calendar's members when it is created.
     */
    public function created(Calendar $calendar): void
    {
        $calendar->members()->syncWithoutDetaching([
            $calendar->owner_id => ['role' => 'owner'],
        ]);
    }
}
