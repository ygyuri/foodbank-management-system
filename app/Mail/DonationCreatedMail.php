<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Donation;
use App\Models\User;

class DonationCreatedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public $donation;
    public $recipient;

    /**
     * Create a new message instance.
     */
    public function __construct(Donation $donation, User $recipient)
    {
        $this->donation = $donation;
        $this->recipient = $recipient;
    }
    public function build()
    {
        return $this->subject('New Donation Created')
            ->view('emails.donation_created')
            ->with([
                'donation' => $this->donation,
                'recipient' => $this->recipient,
            ]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Donation Created Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    // public function content(): Content
    // {
    //     return new Content(
    //         view: 'view.name',
    //     );
    // }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    // public function attachments(): array
    // {
    //     return [];
    // }
}
