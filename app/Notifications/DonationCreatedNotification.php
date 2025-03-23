<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use App\Models\Donation;

class DonationCreatedNotification extends Notification
{
    use Queueable;

    public $donation;
    public $recipientType;
    /**
     * Create a new notification instance.
     */
    public function __construct(Donation $donation, $recipientType)
    {
        $this->donation = $donation;
        $this->recipientType = $recipientType;
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
            ->subject('New Donation Created')
            ->line("A new donation has been created for {$this->recipientType}.")
            ->action('View Donation', url('/donations/' . $this->donation->id))
            ->line('Thank you for your support!');
    }

    public function toDatabase($notifiable)
    {
        return new DatabaseMessage([
            'title' => 'New Donation Created',
            'message' => "A new donation has been created for {$this->recipientType}.",
            'donation_id' => $this->donation->id,
        ]);
    }
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
