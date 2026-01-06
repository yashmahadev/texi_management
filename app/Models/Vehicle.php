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
        'puc_expiry_date',
        'is_driver_owner',
        'owner_name',
        'owner_mobile',
        'owner_aadhaar_number',
        'owner_pancard_number',
    ];

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
}
