<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Owner extends Model
{
    protected $fillable = [
        'name',
        'mobile',
        'address',
        'aadhaar_number',
        'pancard_number',
    ];

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }
}
