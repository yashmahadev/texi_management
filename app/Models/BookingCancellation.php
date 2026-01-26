<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingCancellation extends Model
{
    use HasFactory;

    // This table is immutable - no timestamps
    public $timestamps = false;

    protected $fillable = [
        'booking_id',
        'cancelled_by',
        'cancelled_by_type',
        'cancellation_reason',
        'cancelled_at',
    ];

    protected $casts = [
        'cancelled_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function booking()
    {
        return $this->belongsTo(DirectBooking::class, 'booking_id');
    }

    /**
     * Polymorphic relationship to User or Driver who cancelled
     */
    public function canceller()
    {
        return $this->morphTo('canceller', 'cancelled_by_type', 'cancelled_by');
    }
}
