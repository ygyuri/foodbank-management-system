<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// Import SoftDeletes trait

class DonationRequest extends Model
{
    use HasFactory;
    use SoftDeletes; // Enable soft deletion

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'foodbank_id',
        'donor_id',
        'type',
        'quantity',
        'status',
        'description',   // Added field
        'created_by'  // Tracks who created the request (admin or foodbank)
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => 'string',
        'quantity' => 'integer',
        'status' => 'string',
        'description' => 'string'   // Added cast
    ];

    /**
     * Define relationships.
     */
    public function foodbank()
    {
        return $this->belongsTo(User::class, 'foodbank_id')
                    ->where('role', 'foodbank');
    }

    public function donor()
    {
        return $this->belongsTo(User::class, 'donor_id')
                    ->where('role', 'donor');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    /**
     * Scope a query to only include pending donation requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include approved donation requests.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include rejected donation requests.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function foodbankActivity()
{
    return $this->hasOne(FoodbankActivity::class, 'related_id')
                ->where('activity_type', 'fulfilled_request');
}
}
