<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\RequestFB;
use App\Models\User;

class FoodbankRequestNotification extends Mailable
{
    use Queueable;
    use SerializesModels;

    public $requestFB;
    public $foodbank;
    public $recipient;

    /**
     * Create a new message instance.
     */
    public function __construct(RequestFB $requestFB, User|null $foodbank, User $recipient)
    {
        $this->requestFB = $requestFB;
        $this->foodbank = $foodbank;
        $this->recipient = $recipient;
    }

    public function build()
    {
        return $this->subject('New Request from a Recipient')
            ->markdown('emails.foodbank_request')
            ->with([
                'requestFB' => $this->requestFB,
                'foodbank' => $this->foodbank,
                'recipient' => $this->recipient,
            ]);
    }

    /**
     * Get the message envelope.
     */
    // public function envelope(): Envelope
    // {
    //     return new Envelope(
    //         subject: 'Foodbank Request Notification',
    //     );
    // }

    /**
     * Get the message content definition.
     */
    // public function content(): Content
    // {
    //     return new Content(
    //         markdown: 'emails.foodbank_request',
    //     );
    // }

    // /**
    //  * Get the attachments for the message.
    //  *
    //  * @return array<int, \Illuminate\Mail\Mailables\Attachment>
    //  */
    // public function attachments(): array
    // {
    //     return [];
    // }
}
