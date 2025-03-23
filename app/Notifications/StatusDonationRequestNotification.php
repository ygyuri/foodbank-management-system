<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\DonationRequest;

class StatusDonationRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $donationRequest;
    protected $customMessage;

    /**
     * Create a new notification instance.
     *
     * @param DonationRequest $donationRequest
     */
    public function __construct(DonationRequest $donationRequest)
    {
        $this->donationRequest = $donationRequest;
        $this->customMessage = $this->getCustomMessage();
    }

    /**
     * Determine the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Build the email representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage())
            ->subject('Donation Request Status Updated')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($this->customMessage)
            ->action('View Donation Request', url('/donations/' . $this->donationRequest->id))
            ->line('Thank you for your continued support!');
    }

    /**
     * Build the array representation for database notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        return [
            'message' => $this->customMessage,
            'request_id' => $this->donationRequest->id,
            'type' => $this->donationRequest->type,
            'quantity' => $this->donationRequest->quantity,
            'status' => $this->donationRequest->status,
        ];
    }

    /**
     * Create a custom message based on the status of the donation request.
     *
     * @return string
     */
    protected function getCustomMessage()
    {
        switch ($this->donationRequest->status) {
            case 'approved':
                return "Your donation request for {$this->donationRequest->type} has been approved!";
            case 'rejected':
                return "Unfortunately, your donation request for {$this->donationRequest->type} has been rejected.";
            default:
                return "The status of your donation request has been updated.";
        }
    }
}
