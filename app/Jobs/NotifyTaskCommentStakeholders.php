<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Notifications\TaskCommented;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;

class NotifyTaskCommentStakeholders implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    public function __construct(
        public Task $task,
        public TaskComment $comment,
        public User $actor,
    ) {}

    public function handle(): void
    {
        $recipientIds = collect([
            $this->task->assigned_to,
            $this->task->created_by,
            $this->comment->parent?->user_id,
        ])
            ->filter()
            ->unique()
            ->reject(fn (int $id) => $id === $this->actor->id);

        if ($recipientIds->isEmpty()) {
            return;
        }

        User::query()
            ->whereIn('id', $recipientIds)
            ->get()
            ->each(fn (User $user) => $user->notify(new TaskCommented($this->task, $this->comment, $this->actor)));
    }
}
