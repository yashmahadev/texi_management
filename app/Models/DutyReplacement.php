<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DutyReplacement extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_duty_log_id',
        'original_driver_id',
        'replacement_driver_id',
        'reason',
        'assigned_by',
        'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function dailyDutyLog()
    {
        return $this->belongsTo(DailyDutyLog::class);
    }

    public function originalDriver()
    {
        return $this->belongsTo(Driver::class, 'original_driver_id');
    }

    public function replacementDriver()
    {
        return $this->belongsTo(Driver::class, 'replacement_driver_id');
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
