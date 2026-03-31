<?php

namespace App\Jobs;

use App\Models\MonthlyDuty;
use App\Models\DailyDutyLog;
use App\Services\NotificationService;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateRecurringDutiesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(NotificationService $notificationService, WhatsAppService $whatsapp): void
    {
        Log::info('CreateRecurringDutiesJob: started');

        // Find all duties with is_recurring = true whose end_date is in the current month
        // (i.e. they are the "current" active duty that should spawn next month's copy)
        $today        = Carbon::today();
        $monthStart   = $today->copy()->startOfMonth();
        $monthEnd     = $today->copy()->endOfMonth();

        $recurringDuties = MonthlyDuty::where('is_recurring', true)
            ->whereBetween('end_date', [$monthStart, $monthEnd])
            ->with(['vehicle', 'primaryDriver'])
            ->get();

        Log::info("CreateRecurringDutiesJob: found {$recurringDuties->count()} recurring duties");

        foreach ($recurringDuties as $duty) {
            try {
                $this->createNextMonthDuty($duty, $notificationService, $whatsapp);
            } catch (\Exception $e) {
                Log::error("CreateRecurringDutiesJob: failed for duty #{$duty->id} — " . $e->getMessage());
            }
        }

        Log::info('CreateRecurringDutiesJob: completed');
    }

    protected function createNextMonthDuty(MonthlyDuty $duty, NotificationService $notificationService, WhatsAppService $whatsapp): void
    {
        $nextStart = $duty->start_date->copy()->addMonth();
        $nextEnd   = $duty->end_date->copy()->addMonth();

        // Duplicate guard — skip if a duty already exists for this vehicle in the next month period
        $alreadyExists = MonthlyDuty::where('vehicle_id', $duty->vehicle_id)
            ->where(function ($q) use ($nextStart, $nextEnd) {
                $q->whereBetween('start_date', [$nextStart, $nextEnd])
                  ->orWhereBetween('end_date', [$nextStart, $nextEnd])
                  ->orWhere(function ($q2) use ($nextStart, $nextEnd) {
                      $q2->where('start_date', '<=', $nextStart)
                         ->where('end_date', '>=', $nextEnd);
                  });
            })->exists();

        if ($alreadyExists) {
            Log::info("CreateRecurringDutiesJob: skipping duty #{$duty->id} — next month duty already exists for vehicle {$duty->vehicle_id}");
            return;
        }

        DB::transaction(function () use ($duty, $nextStart, $nextEnd, $notificationService, $whatsapp) {
            // Create next month's duty — same details, new dates, is_recurring stays true
            $newDuty = MonthlyDuty::create([
                'group'               => $duty->group,
                'department_id'       => $duty->department_id,
                'department_name'     => $duty->department_name,
                'officer_name'        => $duty->officer_name,
                'vehicle_id'          => $duty->vehicle_id,
                'primary_driver_id'   => $duty->primary_driver_id,
                'start_date'          => $nextStart->format('Y-m-d'),
                'end_date'            => $nextEnd->format('Y-m-d'),
                'expected_start_time' => $duty->expected_start_time,
                'expected_end_time'   => $duty->expected_end_time,
                'state'               => $duty->state,
                'city'                => $duty->city,
                'pincode'             => $duty->pincode,
                'route_remarks'       => $duty->route_remarks,
                'is_recurring'        => true,
                'created_by'          => $duty->created_by,
            ]);

            // Auto-generate daily logs
            for ($date = $nextStart->copy(); $date->lte($nextEnd); $date->addDay()) {
                DailyDutyLog::create([
                    'monthly_duty_id' => $newDuty->id,
                    'duty_date'       => $date->format('Y-m-d'),
                    'status'          => 'pending',
                ]);
            }

            Log::info("CreateRecurringDutiesJob: created duty #{$newDuty->id} for {$newDuty->department_name} ({$nextStart->format('d M Y')} – {$nextEnd->format('d M Y')})");

            // Notify driver
            $driver  = $newDuty->primaryDriver;
            $vehicle = $newDuty->vehicle;

            if ($driver && $driver->fcm_token) {
                $notificationService->sendNotification(
                    $driver->fcm_token,
                    "🚕 New Monthly Duty Assigned",
                    "Your duty for {$newDuty->department_name} has been renewed for " . $nextStart->format('M Y') . ".",
                    ['link' => route('driver.dashboard')]
                );
            }

            if ($driver) {
                $whatsapp->sendDutyAssignment($driver->mobile_number, [
                    'driver_name'      => $driver->name,
                    'vehicle_number'   => $vehicle->vehicle_number,
                    'reporting_time'   => Carbon::parse($newDuty->expected_start_time)->format('h:i A'),
                    'reporting_address' => $newDuty->department_name,
                ]);
            }
        });
    }
}
