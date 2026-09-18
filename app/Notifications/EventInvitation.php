<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventInvitation extends Notification
{
    use Queueable;

    public function __construct(public Event $event) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'event_id' => $this->event->id,
            'title' => $this->event->title,
            'description' => $this->event->description,
            'start_time' => $this->event->start_at?->toISOString(),
            'end_time' => $this->event->end_at?->toISOString(),
            'inviter' => $this->event->organizer?->name,
            'has_video' => $this->event->has_video,
            'response' => 'needs_action',
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You are invited to: '.$this->event->title)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('You have been invited to an event on Taskly.')
            ->line($this->event->title)
            ->line($this->event->start_at->format('l, F j, Y at g:i A'))
            ->action('View invitation', url('/calendar'))
            ->line('You can accept or decline the invitation from Taskly.');
    }
}
