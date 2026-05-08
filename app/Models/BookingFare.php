<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingFare extends Model
{
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($fare) {
            // Auto-calculate base fare based on total_km if provided
            $base = (float)($fare->base_fare ?? 0);
            $rate = (float)($fare->per_km_rate ?? 0);
            $km   = (int)($fare->total_km ?? 0);

            $fare->calculated_fare = $base + ($rate * $km);
            
            // final_fare logic: adjusted takes precedence
            $fare->final_fare = $fare->admin_adjusted_fare ?? $fare->calculated_fare;
        });
    }

    protected $fillable = [
        'booking_id',
        'base_fare',
        'per_km_rate',
        'total_km',
        'calculated_fare',
        'admin_adjusted_fare',
        'final_fare',
        'adjustment_reason',
        'is_locked',
        'calculated_by',
        'adjusted_by',
    ];

    protected $casts = [
        'base_fare' => 'decimal:2',
        'per_km_rate' => 'decimal:2',
        'total_km' => 'integer',
        'calculated_fare' => 'decimal:2',
        'admin_adjusted_fare' => 'decimal:2',
        'final_fare' => 'decimal:2',
        'is_locked' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function booking()
    {
        return $this->belongsTo(DirectBooking::class, 'booking_id');
    }

    public function calculatedBy()
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }

    public function adjustedBy()
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    /**
     * Get the effective fare (adjusted if exists, otherwise calculated)
     */
    public function getEffectiveFareAttribute()
    {
        return $this->admin_adjusted_fare ?? $this->calculated_fare;
    }
}
