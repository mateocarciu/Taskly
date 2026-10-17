<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;

class NotifyTaskAssignee implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    public function __construct(
        public Task $task,
        public User $actor,
    ) {}

    public function handle(): void
    {
        if (empty($this->task->assigned_to) || $this->task->assigned_to === $this->actor->id) {
            return;
        }

        $this->task->assignee?->notify(new TaskAssigned($this->task, $this->actor));
    }
}
