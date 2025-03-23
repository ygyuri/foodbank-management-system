<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

//use Illuminate\Notifications\Messages\BroadcastMessage;

class RecipientRequestStatusNotification extends Notification
{
    use Queueable;


    protected $request;
    protected $status;

    /**
     * Create a new notification instance.
     */
    public function __construct($request, $status)
    {
        $this->request = $request;
        $this->status = $status;
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
        $statusText = $this->status === 'approved' ? 'approved' : 'rejected';

        return (new MailMessage())
            ->subject('Your Request Status Update')
            ->line("Your request has been {$statusText}.")
            ->action('View Request', url('/requests/' . $this->request->id))
            ->line('Thank you for using our application!');
    }
    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable)
    {
        return [
            'request_id' => $this->request->id,
            'status' => $this->status,
            'message' => "Your request has been {$this->status}.",
        ];
    }

    // /**
    //  * Get the broadcast representation of the notification for real-time updates.
    //  *
    //  * @param  mixed  $notifiable
    //  * @return BroadcastMessage
    //  */
    // public function toBroadcast($notifiable)
    // {
    //     return new BroadcastMessage($this->toArray($notifiable));
    // }
}
