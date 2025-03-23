<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FeedbackNotification extends Notification
{
    use Queueable;

    protected $feedback;
    /**
     * Create a new notification instance.
     */
    public function __construct($feedback)
    {
        $this->feedback = $feedback;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->subject('New Feedback Received')
            ->line($this->feedback->message)
            ->action('View Feedback', url('/feedbacks/' . $this->feedback->id));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable)
    {
        return [
            'message' => $this->feedback->message,
            'sender' => $this->feedback->sender->name,
            'type' => $this->feedback->type,
        ];
    }
}
