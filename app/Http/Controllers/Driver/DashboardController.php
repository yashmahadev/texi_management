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
        
        $today = now()->format('Y-m-d');
        
        // Find today's log for this driver (either primary, replacement or direct)
        $todayLog = \App\Models\DailyDutyLog::where('duty_date', $today)
            ->where(function($query) use ($driverId) {
                // Monthly Duty (Primary)
                $query->whereHas('monthlyDuty', function($q) use ($driverId) {
                    $q->where('primary_driver_id', $driverId);
                })
                // Monthly Duty (Replacement)
                ->orWhereHas('replacements', function($q) use ($driverId) {
                    $q->where('replacement_driver_id', $driverId);
                })
                // Direct Booking
                ->orWhereHas('directBooking', function($q) use ($driverId) {
                    $q->where('driver_id', $driverId);
                });
            })
            ->with(['monthlyDuty.vehicle', 'directBooking.vehicle'])
            ->first();

        $currentDuty = null;
        if ($todayLog) {
            if ($todayLog->monthly_duty_id) {
                $currentDuty = $todayLog->monthlyDuty;
            } else {
                $currentDuty = $todayLog->directBooking;
            }
            $currentDuty->setRelation('dailyLogs', collect([$todayLog]));
        }
        
        // Phase-2: Get active direct bookings
        $directBookingService = app(\App\Services\DirectBookingService::class);
        $activeDirectBookings = $directBookingService->getDriverBookings($driver, ['ASSIGNED', 'ACCEPTED', 'STARTED']);

        return view('driver.dashboard', compact('currentDuty', 'today', 'activeDirectBookings'));
    }
}
