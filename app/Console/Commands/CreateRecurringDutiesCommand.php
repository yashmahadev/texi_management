<?php

namespace App\Console\Commands;

use App\Models\MonthlyDuty;
use App\Models\DailyDutyLog;
use App\Services\NotificationService;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateRecurringDutiesCommand extends Command
{
    protected $signature = 'duties:create-recurring {month? : Target month in Y-m format, e.g. 2026-05}';

    protected $description = 'Create next month recurring duties for monthly duties marked as recurring';

    public function handle(NotificationService $notificationService, WhatsAppService $whatsapp): int
    {
        Log::info('CreateRecurringDutiesCommand: started');

        $monthInput = $this->argument('month');
        $targetMonth = null;

        if (!empty($monthInput)) {
            try {
                $targetMonth = Carbon::createFromFormat('Y-m', $monthInput)->startOfMonth();
            } catch (\Exception $e) {
                $this->error('Invalid month format. Use Y-m, for example: 2026-05');
                Log::error('CreateRecurringDutiesCommand: invalid month input ' . $monthInput);
                return self::FAILURE;
            }
        }

        if ($targetMonth === null) {
            $today = Carbon::today();
            $monthStart = $today->copy()->startOfMonth();
            $monthEnd = $today->copy()->endOfMonth();
        } else {
            $monthStart = $targetMonth->copy()->startOfMonth();
            $monthEnd = $targetMonth->copy()->endOfMonth();
        }

        $recurringDuties = MonthlyDuty::where('is_recurring', true)
            ->whereBetween('end_date', [$monthStart, $monthEnd])
            ->with(['vehicle', 'primaryDriver'])
            ->get();

        $monthLabel = $monthStart->format('F Y');
        Log::info("CreateRecurringDutiesCommand: processing duties ending in {$monthLabel}. Found {$recurringDuties->count()} recurring duties");

        $this->info("Found {$recurringDuties->count()} recurring duties for {$monthLabel}");

        foreach ($recurringDuties as $duty) {
            try {
                $this->createNextMonthDuty($duty, $notificationService, $whatsapp);
            } catch (\Exception $e) {
                Log::error("CreateRecurringDutiesCommand: failed for duty #{$duty->id} — " . $e->getMessage());
                $this->error("Failed duty #{$duty->id}: " . $e->getMessage());
            }
        }

        Log::info('CreateRecurringDutiesCommand: completed');
        $this->info('Recurring duties creation completed.');

        return self::SUCCESS;
    }

    protected function createNextMonthDuty(MonthlyDuty $duty, NotificationService $notificationService, WhatsAppService $whatsapp): void
    {
        $nextStart = $duty->start_date->copy()->addMonth();
        $nextEnd   = $duty->end_date->copy()->addMonth();

        $alreadyExists = MonthlyDuty::where('vehicle_id', $duty->vehicle_id)
            ->where(function ($q) use ($nextStart, $nextEnd) {
                $q->whereBetween('start_date', [$nextStart, $nextEnd])
                  ->orWhereBetween('end_date', [$nextStart, $nextEnd])
                  ->orWhere(function ($q2) use ($nextStart, $nextEnd) {
                      $q2->where('start_date', '<=', $nextStart)
                         ->where('end_date', '>=', $nextEnd);
                  });
            })
            ->exists();

        if ($alreadyExists) {
            Log::info("CreateRecurringDutiesCommand: skipping duty #{$duty->id} — next month duty already exists for vehicle {$duty->vehicle_id}");
            return;
        }

        DB::transaction(function () use ($duty, $nextStart, $nextEnd, $notificationService, $whatsapp) {
            $newDuty = MonthlyDuty::create([
                'group'               => $duty->group,
                'department_id'       => $duty->department_id,
                'department_name'     => $duty->department_name,
                'officer_name'        => $duty->officer_name,
                'vehicle_id'          => $duty->vehicle_id,
                'primary_driver_id'    => $duty->primary_driver_id,
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

            for ($date = $nextStart->copy(); $date->lte($nextEnd); $date->addDay()) {
                DailyDutyLog::create([
                    'monthly_duty_id' => $newDuty->id,
                    'duty_date'       => $date->format('Y-m-d'),
                    'status'          => 'pending',
                ]);
            }

            Log::info("CreateRecurringDutiesCommand: created duty #{$newDuty->id} for {$newDuty->department_name} ({$nextStart->format('d M Y')} – {$nextEnd->format('d M Y')})");

            $driver = $newDuty->primaryDriver;
            $vehicle = $newDuty->vehicle;

            if ($driver && $driver->fcm_token) {
                $notificationService->sendNotification(
                    $driver->fcm_token,
                    '🚕 New Monthly Duty Assigned',
                    "Your duty for {$newDuty->department_name} has been renewed for " . $nextStart->format('M Y') . '.',
                    ['link' => route('driver.dashboard')]
                );
            }

            if ($driver) {
                $whatsapp->sendDutyAssignment($driver->mobile_number, [
                    'driver_name'       => $driver->name,
                    'vehicle_number'    => $vehicle->vehicle_number,
                    'reporting_time'    => Carbon::parse($newDuty->expected_start_time)->format('h:i A'),
                    'reporting_address' => $newDuty->department_name,
                ]);
            }
        });
    }
}
