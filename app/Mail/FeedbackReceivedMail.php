<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class FeedbackReceivedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public $feedback;
    public $sender;

    /**
     * Create a new message instance.
     */
    public function __construct(Feedback $feedback, User $sender)
    {
        $this->feedback = $feedback;
        $this->sender = $sender;
    }


    public function build()
    {
        Log::info('Email Data', [
            'receiverName' => $this->feedback->receiver->name ?? 'User',
            'senderName' => $this->sender->name ?? 'Anonymous',
            'rating' => $this->feedback->rating ?? '0',
            'message' => $this->feedback->message ?? '',  // Possible issue here
            'thankYouNote' => $this->feedback->thank_you_note ?? '',
            'feedbackType' => $this->feedback->type ?? 'Unknown',
            'reference' => $this->feedback->reference ?? '',
        ]);

        return $this->subject('New Feedback Received')
            ->view('emails.feedback_received')
            ->with([
                'receiverName' => (string) ($this->feedback->receiver->name ?? 'User'),
                'senderName' => (string) ($this->sender->name ?? 'Anonymous'),
                'rating' => (string) ($this->feedback->rating ?? '0'),
                'message' => (string) ($this->feedback->message ?? ''),
                'thankYouNote' => (string) ($this->feedback->thank_you_note ?? ''),
                'feedbackType' => (string) ($this->feedback->type ?? 'Unknown'),
                'reference' => (string) ($this->feedback->reference ?? ''),
            ]);
    }
    /**
     * Get the message envelope.
     */
    // public function envelope(): Envelope
    // {
    //     return new Envelope(
    //         subject: 'Feedback Received Mail',
    //     );
    // }

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
