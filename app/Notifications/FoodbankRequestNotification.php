<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use App\Models\User;
use App\Models\RequestFB;

class FoodbankRequestNotification extends Notification
{
    use Queueable;

    protected $req;
    protected $recipient;
    /**
     * Create a new notification instance.
     */
    public function __construct(RequestFB $req, User $recipient)
    {
        $this->req = $req;
        $this->recipient = $recipient;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return ['database', ];
    }
    public function toDatabase($notifiable)
    {
        return [
            'message' => 'A new request has been made by ' . $this->recipient->name,
            'request_id' => $this->req->id,
            'type' => $this->req->type,
            'quantity' => $this->req->quantity,
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    // public function toMail($notifiable)
    // {
    //     return (new MailMessage)
    //         ->subject('New Request Received')
    //         ->line('A new request has been made by ' . $this->recipient->name)
    //         ->action('View Request', url('/requests/' . $this->req->id))
    //         ->line('Thank you for supporting our cause!');
    // }
    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
