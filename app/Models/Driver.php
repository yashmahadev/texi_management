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
        'mobile_number',
        'driving_licence_number',
        'driving_licence_document',
        'aadhaar_number',
        'aadhaar_document',
        'alternate_contact_number',
        'relationship_with_alternate_contact',
        'state',
        'city',
        'pincode',
        'status',
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
}
