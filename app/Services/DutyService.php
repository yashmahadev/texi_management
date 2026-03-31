<?php

namespace App\Services;

use App\Models\MonthlyDuty;
use App\Models\DailyDutyLog;
use App\Models\DutyReplacement;
use App\Models\Driver;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\AuditLogService;
use App\Services\NotificationService;

class DutyService
{
    protected $fcmService;
    protected $whatsapp;
    protected $notificationService;

    public function __construct(
        AuditLogService $auditLogger, 
        FcmService $fcmService, 
        WhatsAppService $whatsapp,
        NotificationService $notificationService
    ) {
        $this->auditLogger = $auditLogger;
        $this->fcmService = $fcmService;
        $this->whatsapp = $whatsapp;
        $this->notificationService = $notificationService;
    }

    public function createMonthlyDuty(array $data, int $creatorId): array
    {
        return DB::transaction(function () use ($data, $creatorId) {
            $vehicle    = \App\Models\Vehicle::findOrFail($data['vehicle_id']);
            $department = \App\Models\Department::findOrFail($data['department_id']);

            $duty = $this->createSingleDuty($data, $creatorId, $vehicle, $department);

            return ['duty' => $duty, 'total_created' => 1];
        });
    }

    protected function createSingleDuty(array $data, int $creatorId, $vehicle, $department, bool $notify = true): MonthlyDuty
    {
        $duty = MonthlyDuty::create([
            'group'               => $data['group'],
            'department_id'       => $data['department_id'],
            'department_name'     => $data['department_name'] ?? $department->name,
            'officer_name'        => $data['officer_name'],
            'vehicle_id'          => $data['vehicle_id'],
            'primary_driver_id'   => $vehicle->driver_id,
            'start_date'          => $data['start_date'],
            'end_date'            => $data['end_date'],
            'expected_start_time' => $data['expected_start_time'],
            'expected_end_time'   => $data['expected_end_time'] ?? null,
            'state'               => $data['state'] ?? null,
            'city'                => $data['city'] ?? null,
            'pincode'             => $data['pincode'] ?? null,
            'route_remarks'       => $data['route_remarks'] ?? null,
            'is_recurring'        => !empty($data['is_recurring']),
            'created_by'          => $creatorId,
        ]);

        // Auto-generate daily logs
        $startDate = Carbon::parse($data['start_date']);
        $endDate   = Carbon::parse($data['end_date']);
        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            DailyDutyLog::create([
                'monthly_duty_id' => $duty->id,
                'duty_date'       => $date->format('Y-m-d'),
                'status'          => 'pending',
            ]);
        }

        $this->auditLogger->log('Create Monthly Duty', 'monthly_duties', $duty->id, "Created duty for {$duty->department_name}");

        if ($notify && $duty->primaryDriver && $duty->primaryDriver->fcm_token) {
            $this->notificationService->sendNotification(
                $duty->primaryDriver->fcm_token,
                "🚕 New Monthly Duty Assigned",
                "You have been assigned a new duty for {$duty->department_name}.",
                ['link' => route('driver.dashboard')]
            );
        }

        if ($notify && $duty->primaryDriver) {
            $this->whatsapp->sendDutyAssignment($duty->primaryDriver->mobile_number, [
                'driver_name'      => $duty->primaryDriver->name,
                'vehicle_number'   => $vehicle->vehicle_number,
                'reporting_time'   => Carbon::parse($duty->expected_start_time)->format('h:i A'),
                'reporting_address' => $duty->department_name,
            ]);
        }

        return $duty;
    }

    public function updateMonthlyDuty(MonthlyDuty $duty, array $data): MonthlyDuty
    {
        return DB::transaction(function () use ($duty, $data) {
            $duty->update([
                'officer_name'        => $data['officer_name'],
                'expected_start_time' => $data['expected_start_time'],
                'expected_end_time'   => $data['expected_end_time'] ?? null,
                'state'               => $data['state'] ?? null,
                'city'                => $data['city'] ?? null,
                'pincode'             => $data['pincode'] ?? null,
                'route_remarks'       => $data['route_remarks'] ?? null,
                'is_recurring'        => !empty($data['is_recurring']),
            ]);

            $this->auditLogger->log('Update Monthly Duty', 'monthly_duties', $duty->id, "Updated duty for {$duty->department_name}");

            return $duty;
        });
    }

    public function startDuty(DailyDutyLog $log, array $data, string $photoPath = null)
    {
        // Business Rules:
        // 1. Cannot start future duty? (Assume yes, strictly today)
        // 2. Only if pending or previously missing (if allowed)?
        // 3. One start per day.

        $log->update([
            'start_time' => now()->format('H:i:s'),
            'start_km' => $data['start_km'],
            'start_photo_path' => $photoPath,
            'status' => 'started',
        ]);

        $this->auditLogger->log('Start Duty', 'daily_duty_logs', $log->id, "Started at {$data['start_km']} KM");
        
        return $log;
    }

    public function endDuty(DailyDutyLog $log, array $data, string $photoPath = null)
    {
        $totalKm = $data['end_km'] - $log->start_km;
        
        $log->update([
            'end_time' => now()->format('H:i:s'),
            'end_km' => $data['end_km'],
            'total_km' => $totalKm,
            'end_photo_path' => $photoPath,
            'status' => 'completed',
        ]);

        $this->auditLogger->log('End Duty', 'daily_duty_logs', $log->id, "Ended at {$data['end_km']} KM. Total: {$totalKm}");

        // FCM Notification to Driver
        $replacement = $log->replacements()->first();
        $driver = null;
        if ($replacement) {
            $driver = $replacement->replacementDriver;
        } elseif ($log->monthlyDuty) {
            $driver = $log->monthlyDuty->primaryDriver;
        } elseif ($log->directBooking) {
            $driver = $log->directBooking->driver;
        }
        
        if ($driver && $driver->fcm_token) {
            $this->notificationService->sendNotification(
                $driver->fcm_token,
                "✅ Trip Completed!",
                "Total Distance: {$totalKm} KM. Summary recorded successfully.",
                ['link' => route('driver.history')]
            );
        }

        // WhatsApp Invoice Notification
        if ($driver) {
            $customerName = $log->monthlyDuty ? $log->monthlyDuty->officer_name : ($log->directBooking ? $log->directBooking->customer_name : 'Customer');
            $this->whatsapp->sendInvoice($driver->mobile_number, [
                'customer_name' => $customerName,
                'amount' => '0', // Placeholder: logic for price not yet implemented in Phase 1
                'bill_no' => 'BILL-' . $log->id
            ]);
        }

        return $log;
    }

    public function assignReplacement(DailyDutyLog $log, int $replacementDriverId, string $reason, int $assignedBy)
    {
        return DB::transaction(function () use ($log, $replacementDriverId, $reason, $assignedBy) {
            $originalDriverId = null;
            $vehicleNumber = 'N/A';
            $expectedStartTime = '00:00:00';
            $reportingAddress = 'N/A';

            if ($log->monthlyDuty) {
                $originalDriverId = $log->monthlyDuty->primary_driver_id;
                $vehicleNumber = $log->monthlyDuty->vehicle->vehicle_number;
                $expectedStartTime = $log->monthlyDuty->expected_start_time;
                $reportingAddress = $log->monthlyDuty->department_name;
            } elseif ($log->directBooking) {
                $originalDriverId = $log->directBooking->driver_id;
                $vehicleNumber = $log->directBooking->vehicle->vehicle_number;
                $expectedStartTime = $log->directBooking->booking_datetime->format('H:i:s');
                $reportingAddress = $log->directBooking->pickup_location;
            }
            
            DutyReplacement::create([
                'daily_duty_log_id' => $log->id,
                'original_driver_id' => $originalDriverId,
                'replacement_driver_id' => $replacementDriverId,
                'reason' => $reason,
                'assigned_by' => $assignedBy,
                'assigned_at' => now(),
            ]);

            $log->update(['status' => 'replaced']);

            $this->auditLogger->log('Assign Replacement', 'duty_replacements', $log->id, "Replaced with Driver ID {$replacementDriverId}");

            // Push Notification to Replacement Driver
            $replacementDriver = Driver::find($replacementDriverId);
            if ($replacementDriver && $replacementDriver->fcm_token) {
                $this->notificationService->sendNotification(
                    $replacementDriver->fcm_token,
                    "🔄 Replacement Duty Assigned",
                    "You are assigned as a replacement for today's duty ({$log->duty_date->toAppDate()})",
                    ['link' => route('driver.dashboard')]
                );
            }

            // WhatsApp Notification to Replacement Driver
            if ($replacementDriver) {
                $this->whatsapp->sendDutyAssignment($replacementDriver->mobile_number, [
                    'driver_name' => $replacementDriver->name,
                    'vehicle_number' => $vehicleNumber,
                    'reporting_time' => Carbon::parse($expectedStartTime)->format('h:i A'),
                    'reporting_address' => $reportingAddress
                ]);
            }
        });
    }
}
