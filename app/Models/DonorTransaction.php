<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DonorTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'donor_id',
        'transaction_type',
        'related_id',
        'quantity',
        'description',
        'timestamp',
    ];

    public function donor()
    {
        return $this->belongsTo(User::class, 'donor_id');
    }

    public function relatedDonation()
    {
        return $this->belongsTo(Donation::class, 'related_id');
    }
}