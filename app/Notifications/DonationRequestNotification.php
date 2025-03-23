<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\DonationRequest;

// ✅ Add this import

class DonationRequestNotification extends Notification
{
    use Queueable;

    protected $donationRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct(DonationRequest $donationRequest)
    {
        $this->donationRequest = $donationRequest;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [ 'database'];
    }

    // /**
    //  * Get the mail representation of the notification.
    //  *
    //  * @param  mixed  $notifiable
    //  * @return \Illuminate\Notifications\Messages\MailMessage
    //  */
    // public function toMail($notifiable)
    // {
    //     return (new MailMessage)
    //         ->subject('New Donation Request Created')
    //         ->greeting('Hello ' . $notifiable->name . ',')
    //         ->line('A new donation request has been created for ' . $this->donationRequest->type . '.')
    //         ->line('Quantity: ' . $this->donationRequest->quantity)
    //         ->line('Status: Pending')
    //         ->action('View Request', url('/donations/' . $this->donationRequest->id))
    //         ->line('Thank you for your generosity!');
    // }
      /**
     * Get the array representation of the notification for database storage.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        return [
            'message' => $this->donationRequest->type . ' donation request created successfully.',
            'request_id' => $this->donationRequest->id,
            'type' => $this->donationRequest->type,
            'quantity' => $this->donationRequest->quantity,
            'status' => 'pending',
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable)
    {
        return [
            'id' => $this->donationRequest->id,
            'type' => $this->donationRequest->type,
            'status' => $this->donationRequest->status,
            'message' => 'Donation request status updated to ' . $this->donationRequest->status,
        ];
    }
}
