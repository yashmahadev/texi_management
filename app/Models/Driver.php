<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Driver extends Authenticatable
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'name',
        'age',
        'mobile_number',
        'driving_licence_number',
        'driving_licence_document',
        'dl_expiry',
        'aadhaar_number',
        'aadhaar_document',
        'alternate_contact_number',
        'relationship_with_alternate_contact',
        'state',
        'city',
        'pincode',
        'address',
        'is_police_verified',
        'police_verification_document',
        'status',
        'fcm_token',
    ];

    public function vehicle()
    {
        return $this->hasOne(Vehicle::class);
    }

    // Relationships
    public function monthlyDuties()
    {
        return $this->hasMany(MonthlyDuty::class, 'primary_driver_id');
    }

    public function replacementsAsOriginal()
    {
        return $this->hasMany(DutyReplacement::class, 'original_driver_id');
    }

    public function replacementsAsReplacement()
    {
        return $this->hasMany(DutyReplacement::class, 'replacement_driver_id');
    }

    // Phase-2: Direct Booking Relationships
    public function directBookingAssignments()
    {
        return $this->hasMany(BookingAssignment::class);
    }

    public function activeDirectBookings()
    {
        return $this->hasManyThrough(
            DirectBooking::class,
            BookingAssignment::class,
            'driver_id', // Foreign key on booking_assignments table
            'id', // Foreign key on direct_bookings table
            'id', // Local key on drivers table
            'booking_id' // Local key on booking_assignments table
        )
        ->where('booking_assignments.is_active', true)
        ->whereIn('direct_bookings.status', ['ASSIGNED', 'ACCEPTED', 'STARTED']);
    }
}
