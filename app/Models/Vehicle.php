<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($vehicle) {
            if ($vehicle->driver) {
                $vehicle->driver->delete();
            }
        });
    }

    protected $fillable = [
        'vehicle_number',
        'vehicle_type',
        'status',
        'driver_id',
        'owner_id',
        'puc_expiry_date',
        'fuel_type',
        'transmission_type',
        'color',
        'make_model',
        'vehicle_type_custom',
        'pass_type',
        'challan_count',
        'challan_amount',
        'rc_book_path',
        'insurance_details',
        'is_driver_owner',
        'owner_name',
        'owner_mobile',
        'owner_aadhaar_number',
        'owner_pancard_number',
    ];

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    protected $casts = [
        'puc_expiry_date' => 'date',
        'is_driver_owner' => 'boolean',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function monthlyDuties()
    {
        return $this->hasMany(MonthlyDuty::class);
    }

    // Phase-2: Direct Booking Relationship
    public function directBookingAssignments()
    {
        return $this->hasMany(BookingAssignment::class);
    }
}
