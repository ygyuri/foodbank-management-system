<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RequestFB extends Model
{
    use HasFactory;
    use SoftDeletes; // Enable soft deletes

    protected $table = 'requestsfbs'; // Explicitly set the correct table name

    protected $fillable = [
        'foodbank_id', 'recipient_id', 'type', 'quantity', 'status'
    ];


    protected $casts = [
        'quantity' => 'integer', // Ensure quantity is always cast to an integer
    ];

    /**
     * Get the foodbank that owns the request.
     */
    public function foodbank()
    {
        return $this->belongsTo(User::class, 'foodbank_id');
    }
     /**
     * Get the recipient who made the request.
     */
    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
