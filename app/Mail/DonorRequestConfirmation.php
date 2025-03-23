<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\DonationRequest;
use App\Models\User;

class DonorRequestConfirmation extends Mailable
{
    use Queueable;
    use SerializesModels;

    public $donationRequest;
    public $donor;
    public $foodbank;

    /**
     * Create a new message instance.
     */
    public function __construct(DonationRequest $donationRequest, User $donor, $foodbank = null)
    {
        $this->donationRequest = $donationRequest;
        $this->donor = $donor;
        $this->foodbank = $foodbank;
    }

    public function build()
    {
        return $this->subject('Donation Request Confirmation')
                    ->markdown('emails.donor_request_confirmation');
    }
    // /**
    //  * Get the message envelope.
    //  */
    // public function envelope(): Envelope
    // {
    //     return new Envelope(
    //         subject: 'Donor Request Confirmation',
    //     );
    // }

    // /**
    //  * Get the message content definition.
    //  */
    // public function content(): Content
    // {
    //     return new Content(
    //         markdown: 'emails.donor_request_confirmation',
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
