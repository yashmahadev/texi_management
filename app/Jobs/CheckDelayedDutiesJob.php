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
        $startThreshold = config('taxi.duty_start_threshold_minutes', 30);
        $endThreshold = config('taxi.duty_end_threshold_minutes', 60);
        $expectedDuration = config('taxi.duty_expected_duration_hours', 12);
        
        $now = now();
        
        // 1. Check for START delays (Pending logs)
        $delayedStartLogs = DailyDutyLog::with(['monthlyDuty.primaryDriver', 'monthlyDuty.vehicle'])
            ->where('status', 'pending')
            ->whereDate('duty_date', $now)
            ->whereHas('monthlyDuty', function ($q) use ($now, $startThreshold) {
                $q->where('expected_start_time', '<', $now->copy()->subMinutes($startThreshold)->format('H:i:s'));
            })
            ->get();

        foreach ($delayedStartLogs as $log) {
            $this->sendAlert($whatsapp, $log, 'start_delay');
        }

        // 2. Check for END delays (Started logs)
        // If duty started at T, expected end is T + duration. 
        // Alert if now > T + duration + endThreshold.
        $delayedEndLogs = DailyDutyLog::with(['monthlyDuty.primaryDriver', 'monthlyDuty.vehicle'])
            ->where('status', 'started')
            ->whereDate('duty_date', $now)
            ->whereHas('monthlyDuty', function ($q) use ($now, $endThreshold, $expectedDuration) {
                // Carbon doesn't easily let us add hours to a time string in SQL.
                // We'll filter in PHP for end delays as it's more reliable.
            })
            ->get();

        foreach ($delayedEndLogs as $log) {
            $expectedStartTimeStr = $log->monthlyDuty->expected_start_time;
            $expectedEndTimeStr = $log->monthlyDuty->expected_end_time;
            
            $expectedStartTime = Carbon::parse($log->duty_date->format('Y-m-d') . ' ' . $expectedStartTimeStr);
            
            if ($expectedEndTimeStr) {
                // Use dynamic end time
                $expectedEndTime = Carbon::parse($log->duty_date->format('Y-m-d') . ' ' . $expectedEndTimeStr);
                // Handle late-night cross-over (if end < start, assume next day)
                if ($expectedEndTime->lessThan($expectedStartTime)) {
                    $expectedEndTime->addDay();
                }
            } else {
                // Fallback to default duration
                $expectedEndTime = $expectedStartTime->copy()->addHours($expectedDuration);
            }
            
            $alertTime = $expectedEndTime->copy()->addMinutes($endThreshold);

            if ($now->greaterThan($alertTime)) {
                $this->sendAlert($whatsapp, $log, 'end_delay');
            }
        }
    }

    protected function sendAlert(WhatsAppService $whatsapp, DailyDutyLog $log, string $type): void
    {
        $cacheKey = "duty_{$type}_alert_{$log->id}";
        if (cache()->has($cacheKey)) {
            return;
        }

        $replacement = $log->replacements()->first();
        $driver = $replacement ? $replacement->replacementDriver : $log->monthlyDuty->primaryDriver;
        
        if (!$driver || !$driver->mobile_number) {
            return;
        }

        $details = [
            'department' => $log->monthlyDuty->department_name,
            'vehicle' => $log->monthlyDuty->vehicle->vehicle_number,
            'time' => $log->monthlyDuty->expected_start_time,
            'type' => $type === 'start_delay' ? 'Start' : 'End',
        ];

        // WhatsApp Delay Alert
        $whatsapp->sendDelayAlert($driver->mobile_number, $details);

        // FCM Delay Alert
        if ($driver->fcm_token) {
            $notificationService = app(\App\Services\NotificationService::class);
            $notificationService->sendNotification(
                $driver->fcm_token,
                "⚠️ Alert: Duty Delay",
                "Your duty for {$details['department']} was due to start at {$details['time']}. Please report status.",
                ['link' => route('driver.dashboard')]
            );
        }
        
        cache()->put($cacheKey, true, now()->addDay());
        
        \App\Models\AuditLog::create([
            'entity_type' => 'daily_duty_logs',
            'entity_id' => $log->id,
            'action' => "{$type}_sent",
            'remarks' => ucfirst(str_replace('_', ' ', $type)) . " sent to " . $driver->name,
        ]);
    }
}
