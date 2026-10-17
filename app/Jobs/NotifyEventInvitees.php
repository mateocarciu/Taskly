<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\User;
use App\Notifications\EventInvitation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;

class NotifyEventInvitees implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * @param  array<int>  $userIds
     */
    public function __construct(
        public Event $event,
        public array $userIds,
    ) {}

    public function handle(): void
    {
        if (empty($this->userIds)) {
            return;
        }

        User::query()
            ->whereIn('id', $this->userIds)
            ->get()
            ->each(fn (User $user) => $user->notify(new EventInvitation($this->event)));
    }
}
