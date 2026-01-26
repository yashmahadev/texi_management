<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'mobile',
        'email',
        'address',
        'notes',
        'status',
    ];

    /**
     * Scope to filter active customers
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get all bookings for this customer
     */
    public function directBookings()
    {
        return $this->hasMany(DirectBooking::class);
    }
}
