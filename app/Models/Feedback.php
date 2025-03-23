<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Feedback extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'feedbacks';

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'thank_you_note',
        'rating',
        'message',
        'type',
        'reference', // New writable field
    ];

    protected $casts = [
        'rating' => 'integer',
        'type' => 'string',
    ];

    /**
     * Get the sender of the feedback.
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the receiver of the feedback.
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    // /**
    //  * Get the recipient request to foodbank.
    //  */
    // public function requestFb()
    // {
    //     return $this->belongsTo(RequestFb::class, 'request_fb_id');
    // }

    // /**
    //  * Get the foodbank request to donor.
    //  */
    // public function donationRequest()
    // {
    //     return $this->belongsTo(DonationRequest::class, 'donation_request_id');
    // }

    // /**
    //  * Get the donation feedback (donor to foodbank).
    //  */
    // public function donation()
    // {
    //     return $this->belongsTo(Donation::class, 'donation_id');
    // }
}
