<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Donation;
use Illuminate\Support\Facades\Log;

class DonationStatusNotification extends Notification
{
    use Queueable;

    protected $donation;

    /**
     * Create a new notification instance.
     */
    public function __construct(Donation $donation)
    {
        $this->donation = $donation;
        Log::info("DonationStatusNotification created for Donation ID: " . $donation->id);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        Log::info("Notification channels for Donation ID " . $this->donation->id . ": mail, database");
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        Log::info("Preparing email for Donation ID: " . $this->donation->id);
        return (new MailMessage())
            ->subject('Donation Status Updated')
            ->line('The status of a donation has been updated.')
            ->line('Donation ID: ' . $this->donation->id)
            ->line('Status: ' . $this->donation->status)
            ->action('View Donation', url('/donations/' . $this->donation->id))
            ->line('Thank you for your support!');
    }


    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable)
    {
        Log::info("Converting notification to array for Donation ID: " . $this->donation->id);
        return [
            'donation_id' => $this->donation->id,
            'status' => $this->donation->status,
        ];
    }
}
