<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyDutyLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'monthly_duty_id',
        'duty_date',
        'start_time',
        'end_time',
        'start_km',
        'end_km',
        'total_km',
        'status',
        'start_photo_path',
        'end_photo_path',
    ];

    protected $casts = [
        'duty_date' => 'date',
    ];

    public function monthlyDuty()
    {
        return $this->belongsTo(MonthlyDuty::class);
    }

    public function replacements()
    {
        return $this->hasMany(DutyReplacement::class);
    }
}
