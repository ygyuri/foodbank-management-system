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

class RecipientRequestConfirmation extends Mailable
{
    use Queueable;
    use SerializesModels;

    public $requestFB;
    public $foodbank;
    public $recipient;

    /**
     * Create a new message instance.
     */
    public function __construct(RequestFB $requestFB, ?User $foodbank = null, User $recipient)
    {
        $this->requestFB = $requestFB;
        $this->foodbank = $foodbank;
        $this->recipient = $recipient;
    }

    public function build()
    {
        $foodbankMessage = $this->foodbank
            ? 'Your request has been sent to ' . $this->foodbank->name . '.'
            : 'Your request has been submitted, but no foodbank is assigned yet.';

        return $this->subject('Request Submitted Successfully')
            ->markdown('emails.recipient_request')
            ->with([
                'requestFB' => $this->requestFB,
                'foodbank' => $this->foodbank,
                'recipient' => $this->recipient,
                'foodbankMessage' => $foodbankMessage,  // Pass the custom message
            ]);
    }


    // /**
    //  * Get the message envelope.
    //  */
    // public function envelope(): Envelope
    // {
    //     return new Envelope(
    //         subject: 'Recipient Request Confirmation',
    //     );
    // }

    // /**
    //  * Get the message content definition.
    //  */
    // public function content(): Content
    // {
    //     return new Content(
    //         markdown: 'emails.recipient_request',
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
