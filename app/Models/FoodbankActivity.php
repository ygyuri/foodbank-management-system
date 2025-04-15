<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoodbankActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'foodbank_id',
        'activity_type',
        'related_id',
        'quantity',
        'description',
        'timestamp',
    ];

    public function foodbank()
    {
        return $this->belongsTo(User::class, 'foodbank_id');
    }

    public function relatedDonation()
    {
        return $this->belongsTo(Donation::class, 'related_id');
    }

    public function relatedRequest()
    {
        return $this->belongsTo(DonationRequest::class, 'related_id');
    }


    public function getActivityTypeAttribute($value)
    {
        return ucfirst(str_replace('_', ' ', $value));
    }

    public function setActivityTypeAttribute($value)
    {
        $this->attributes['activity_type'] = strtolower(str_replace(' ', '_', $value));
    }
}