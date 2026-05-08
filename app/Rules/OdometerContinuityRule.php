<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class OdometerContinuityRule implements ValidationRule
{
    protected $logId;

    public function __construct($logId = null)
    {
        $this->logId = $logId;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $currentLog = \App\Models\DailyDutyLog::find($this->logId);
        if (!$currentLog) return;

        // Resolve vehicle
        $vehicleId = $currentLog->monthlyDuty?->vehicle_id ?? $currentLog->directBooking?->vehicle_id;
        
        if (!$vehicleId) return;

        // Find the last completed log for this vehicle before this date
        $lastLog = \App\Models\DailyDutyLog::where(function($q) use ($currentLog) {
                $q->whereHas('monthlyDuty', fn($mq) => $mq->where('vehicle_id', $currentLog->monthlyDuty?->vehicle_id ?? $currentLog->directBooking?->vehicle_id))
                  ->orWhereHas('directBooking', fn($bq) => $bq->where('vehicle_id', $currentLog->monthlyDuty?->vehicle_id ?? $currentLog->directBooking?->vehicle_id));
            })
            ->where('id', '!=', $this->logId)
            ->where('duty_date', '<=', $currentLog->duty_date)
            ->whereNotNull('end_km')
            ->where('status', 'completed')
            ->orderBy('duty_date', 'desc')
            ->orderBy('end_time', 'desc')
            ->first();

        if ($lastLog && $value < $lastLog->end_km) {
            $fail("The start KM ($value) cannot be less than the last recorded end KM ({$lastLog->end_km}) for this vehicle on {$lastLog->duty_date->format('d M')}.");
        }
    }
}
