<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingStatusLog extends Model
{
    use HasFactory;

    // This table is immutable - no updated_at
    const UPDATED_AT = null;

    protected $fillable = [
        'booking_id',
        'from_status',
        'to_status',
        'changed_by',
        'changed_by_type',
        'remarks',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Relationships
     */
    public function booking()
    {
        return $this->belongsTo(DirectBooking::class, 'booking_id');
    }

    /**
     * Polymorphic relationship to User or Driver who made the change
     */
    public function changer()
    {
        return $this->morphTo('changer', 'changed_by_type', 'changed_by');
    }
}
