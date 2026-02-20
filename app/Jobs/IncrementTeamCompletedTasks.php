<?php

namespace App\Jobs;

use App\Models\Team;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class IncrementTeamCompletedTasks implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $teamId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Team::query()->where('id', $this->teamId)->increment('count_completed_tasks');
    }
}
