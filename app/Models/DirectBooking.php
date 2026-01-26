<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DirectBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_number',
        'customer_id',
        'customer_name',
        'customer_mobile',
        'pickup_location',
        'drop_location',
        'booking_datetime',
        'booking_end_datetime',
        'estimated_km',
        'actual_km',
        'status',
        'notes',
        'base_fare',
        'per_km_rate',
        'created_by',
        'driver_id',
        'vehicle_id',
    ];

    protected $casts = [
        'booking_datetime' => 'datetime',
        'booking_end_datetime' => 'datetime',
        'estimated_km' => 'integer',
        'actual_km' => 'integer',
        'base_fare' => 'decimal:2',
        'per_km_rate' => 'decimal:2',
    ];

    /**
     * Boot method to auto-generate booking number
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            if (empty($booking->booking_number)) {
                $booking->booking_number = self::generateBookingNumber();
            }
        });
    }

    /**
     * Generate unique booking number in format BK-YYYYMMDD-XXXX
     */
    public static function generateBookingNumber()
    {
        $date = now()->format('Ymd');
        $prefix = "BK-{$date}-";
        
        // Get the last booking number for today
        $lastBooking = self::where('booking_number', 'like', "{$prefix}%")
            ->orderBy('booking_number', 'desc')
            ->first();

        if ($lastBooking) {
            $lastNumber = (int) substr($lastBooking->booking_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Relationships
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments()
    {
        return $this->hasMany(BookingAssignment::class, 'booking_id');
    }

    public function activeAssignment()
    {
        return $this->hasOne(BookingAssignment::class, 'booking_id')->where('is_active', true);
    }

    public function statusLogs()
    {
        return $this->hasMany(BookingStatusLog::class, 'booking_id');
    }

    public function fare()
    {
        return $this->hasOne(BookingFare::class, 'booking_id');
    }

    public function cancellation()
    {
        return $this->hasOne(BookingCancellation::class, 'booking_id');
    }

    public function dailyLogs()
    {
        return $this->hasMany(DailyDutyLog::class, 'direct_booking_id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Accessors
     */
    public function getCurrentDriverAttribute()
    {
        return $this->activeAssignment?->driver;
    }

    public function getCurrentVehicleAttribute()
    {
        return $this->activeAssignment?->vehicle;
    }

    /**
     * Scopes
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('booking_datetime', '>', now())
            ->whereIn('status', ['CREATED', 'ASSIGNED', 'ACCEPTED']);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['ASSIGNED', 'ACCEPTED', 'STARTED']);
    }
}
