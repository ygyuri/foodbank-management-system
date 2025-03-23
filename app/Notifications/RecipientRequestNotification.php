<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\RequestFB;
use App\Models\User;

class RecipientRequestNotification extends Notification
{
    use Queueable;

    protected $req;
    protected $foodbank;

    /**
     * Create a new notification instance.
     */
    public function __construct(RequestFB $req, ?User $foodbank = null)
    {
        $this->req = $req;
        $this->foodbank = $foodbank;
    }
    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return ['database',];
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => 'Your request to ' . ($this->foodbank->name ?? 'Unknown Foodbank') . ' has been submitted successfully.',
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
    //         ->subject('Request Submitted Successfully')
    //         ->line('Your request to ' . ($this->foodbank->name ?? 'Unknown Foodbank') . ' has been submitted.')
    //         ->action('View Request', url('/requests/' . $this->req->id))
    //         ->line('Thank you for using our service!');
    // }

    // /**
    //  * Get the array representation of the notification.
    //  *
    //  * @return array<string, mixed>
    //  */
    // public function toArray(object $notifiable): array
    // {
    //     return [
    //         //
    //     ];
    // }
}
