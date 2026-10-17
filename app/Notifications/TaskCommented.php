<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class TaskCommented extends Notification
{
    use Queueable;

    public function __construct(public Task $task, public TaskComment $comment, public User $actor) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'commenter' => $this->actor->name,
            'comment' => $this->excerpt(),
            'is_reply' => $this->comment->parent_id !== null,
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New comment on: '.$this->task->title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->actor->name.' commented on a task you are following on Taskly.')
            ->line($this->task->title)
            ->line('"'.$this->excerpt().'"')
            ->action('View comment', url('/tasks?id='.$this->task->id))
            ->line('Open the task to read the full conversation.');
    }

    /**
     * A short plain-text version of the comment for previews.
     */
    private function excerpt(): string
    {
        return Str::limit(strip_tags($this->comment->body), 140);
    }
}
