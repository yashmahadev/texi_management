<?php

namespace App\Jobs;

use App\Models\DailyDutyLog;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class CheckDelayedDutiesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(WhatsAppService $whatsapp): void
    {
        $gracePeriodMinutes = 30;
        $now = now();
        
        $delayedLogs = DailyDutyLog::with(['monthlyDuty.primaryDriver', 'monthlyDuty.vehicle'])
            ->where('status', 'pending')
            ->whereDate('duty_date', $now)
            ->whereHas('monthlyDuty', function ($q) use ($now, $gracePeriodMinutes) {
                // Expected start time is past by grace period
                // We need to compare time part.
                // Assuming expected_start_time is 'H:i:s'
                // This query logic might be complex in SQL for "time + minutes".
                // Easier to filter in PHP if dataset small, but for prod use SQL.
                // "TIME_TO_SEC(TIMEDIFF(NOW(), expected_start_time)) > ..." etc.
                // Or just: expected_start_time < now()->subMinutes(30)->format('H:i:s')
                $q->where('expected_start_time', '<', $now->subMinutes($gracePeriodMinutes)->format('H:i:s'));
            })
            ->get();

        foreach ($delayedLogs as $log) {
            $cacheKey = "duty_delay_alert_{$log->id}";
            if (!cache()->has($cacheKey)) {
                // Check if there is a replacement driver for this log
                $replacement = $log->replacements()->where('status', 'approved')->first();
                $driver = $replacement ? $replacement->replacementDriver : $log->monthlyDuty->primaryDriver;
                
                if (!$driver || !$driver->mobile_number) {
                    continue;
                }

                $details = [
                    'department' => $log->monthlyDuty->department_name,
                    'vehicle' => $log->monthlyDuty->vehicle->vehicle_number,
                    'time' => $log->monthlyDuty->expected_start_time,
                ];
                
                $whatsapp->sendDelayAlert($driver->mobile_number, $details);
                
                cache()->put($cacheKey, true, now()->addDay());
                
                // Also log to audit
                \App\Models\AuditLog::create([
                    'entity_type' => 'daily_duty_logs',
                    'entity_id' => $log->id,
                    'action' => 'delay_alert_sent',
                    'performed_by' => null, // System action
                    'remarks' => "Delay alert sent to " . ($replacement ? "Replacement: " : "Primary: ") . $driver->name,
                ]);
            }
        }
    }
}
