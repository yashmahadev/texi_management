<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\MonthlyDuty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $driver = Auth::guard('driver')->user();
        $driverId = $driver->id;
        
        // Get current active monthly duty
        // Logic: Duty where primary_driver is me, AND date range covers today.
        // OR checks for replacements assignment.
        // For simplicity: Check primary first.
        
        $today = now()->format('Y-m-d');
        
        $currentDuty = MonthlyDuty::where('primary_driver_id', $driverId)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->first();

        if ($currentDuty) {
            // Ensure today's log exists
            $todayLog = \App\Models\DailyDutyLog::firstOrCreate(
                [
                    'monthly_duty_id' => $currentDuty->id,
                    'duty_date' => $today,
                ],
                [
                    'status' => 'pending',
                ]
            );
            
            $currentDuty->load(['vehicle']);
            $currentDuty->setRelation('dailyLogs', collect([$todayLog]));
        } else {
            // Check if I am a replacement for TODAY
            $replacementLog = \App\Models\DailyDutyLog::whereDate('duty_date', $today)
                ->whereHas('replacements', function($q) use ($driverId) {
                    $q->where('replacement_driver_id', $driverId);
                })
                ->with(['monthlyDuty.vehicle'])
                ->first();

            if ($replacementLog) {
                $currentDuty = $replacementLog->monthlyDuty;
                $currentDuty->setRelation('dailyLogs', collect([$replacementLog]));
            }
        }
        
        // Phase-2: Get active direct bookings
        $directBookingService = app(\App\Services\DirectBookingService::class);
        $activeDirectBookings = $directBookingService->getDriverBookings($driver, ['ASSIGNED', 'ACCEPTED', 'STARTED']);

        return view('driver.dashboard', compact('currentDuty', 'today', 'activeDirectBookings'));
    }
}
