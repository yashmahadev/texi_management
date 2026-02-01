<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\MonthlyDuty;
use App\Models\DailyDutyLog;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $driverId = $request->user()->id;
        $today = now()->format('Y-m-d');

        // Logic matched with web DashboardController
        $currentDuty = MonthlyDuty::where('primary_driver_id', $driverId)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->first();

        if ($currentDuty) {
            $todayLog = DailyDutyLog::firstOrCreate(
                [
                    'monthly_duty_id' => $currentDuty->id,
                    'duty_date' => $today,
                ],
                [
                    'status' => 'pending',
                ]
            );
            
            $currentDuty->load(['vehicle']);
            // Add todayLog as a property for easier API consumption
            $currentDuty->today_log = $todayLog;
        } else {
            // Check replacement
            $replacementLog = DailyDutyLog::whereDate('duty_date', $today)
                ->whereHas('replacements', function($q) use ($driverId) {
                    $q->where('replacement_driver_id', $driverId);
                })
                ->with(['monthlyDuty.vehicle'])
                ->first();

            if ($replacementLog) {
                $currentDuty = $replacementLog->monthlyDuty;
                $currentDuty->today_log = $replacementLog;
                $currentDuty->is_replacement = true;
            }
        }

        return response()->json([
            'success' => true,
            'date' => $today,
            'current_duty' => $currentDuty,
            // Additional data useful for mobile
            'driver' => $request->user()
        ]);
    }
}
