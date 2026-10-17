<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssigned extends Notification
{
    use Queueable;

    public function __construct(public Task $task, public User $actor) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'assigner' => $this->actor->name,
            'due_date' => $this->task->due_date?->toISOString(),
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('You have been assigned: '.$this->task->title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->actor->name.' assigned you to a task on Taskly.')
            ->line($this->task->title)
            ->action('View task', url('/tasks?id='.$this->task->id));

        if ($this->task->due_date) {
            $mail->line('Due: '.$this->task->due_date->format('l, F j, Y'));
        }

        return $mail->line('Open the task to see all the details.');
    }
}
