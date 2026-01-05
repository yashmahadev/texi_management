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
        $driverId = Auth::guard('driver')->id();
        
        // Get current active monthly duty
        // Logic: Duty where primary_driver is me, AND date range covers today.
        // OR checks for replacements assignment.
        // For simplicity: Check primary first.
        
        $today = now()->format('Y-m-d');
        
        $currentDuty = MonthlyDuty::where('primary_driver_id', $driverId)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->with(['vehicle', 'dailyLogs' => function($q) use ($today) {
                $q->where('duty_date', $today);
            }])
            ->first();

        if (!$currentDuty) {
            // Check if I am a replacement for TODAY
            $replacementLog = \App\Models\DailyDutyLog::whereDate('duty_date', $today)
                ->whereHas('replacements', function($q) use ($driverId) {
                    $q->where('replacement_driver_id', $driverId);
                })
                ->with(['monthlyDuty.vehicle'])
                ->first();

            if ($replacementLog) {
                $currentDuty = $replacementLog->monthlyDuty;
                // Manually set the dailyLogs relation to include only this log, matching view expectations
                $currentDuty->setRelation('dailyLogs', collect([$replacementLog]));
            }
        }

        return view('driver.dashboard', compact('currentDuty', 'today'));
    }
}
