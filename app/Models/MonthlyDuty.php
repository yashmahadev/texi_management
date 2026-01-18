<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyDuty extends Model
{
    use HasFactory;

    protected $fillable = [
        'group',
        'department_id',
        'department_name',
        'officer_name',
        'vehicle_id',
        'primary_driver_id',
        'start_date',
        'end_date',
        'expected_start_time',
        'expected_end_time',
        'state',
        'city',
        'pincode',
        'created_by',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function primaryDriver()
    {
        return $this->belongsTo(Driver::class, 'primary_driver_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dailyLogs()
    {
        return $this->hasMany(DailyDutyLog::class);
    }
}
