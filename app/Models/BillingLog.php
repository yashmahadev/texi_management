<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingLog extends Model
{
    protected $fillable = [
        'daily_duty_log_id',
        'start_time',
        'end_time',
        'start_km',
        'end_km',
        'total_km',
        'status',
        'created_by',
    ];

    public function dailyLog()
    {
        return $this->belongsTo(DailyDutyLog::class, 'daily_duty_log_id');
    }
}
