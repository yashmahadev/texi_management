<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyDutyLog;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Stats (Unfiltered for overview)
        $pendingDuties = DailyDutyLog::whereDate('duty_date', now())
            ->where('status', 'pending')
            ->count();
            
        $completedDuties = DailyDutyLog::whereDate('duty_date', now())
            ->where('status', 'completed')
            ->count();

        $activeVehicles = \App\Models\Vehicle::where('status', 'active')->count();
        $activeDrivers = \App\Models\Driver::where('status', 'active')->count();

        // Monthly Stats
        $currentMonthStart = now()->startOfMonth();
        $monthlyKm = DailyDutyLog::whereDate('duty_date', '>=', $currentMonthStart)
            ->sum('total_km');
        $missingDuties = DailyDutyLog::where('status', 'missing')->count();

        // Pending Duties Table
        $todaysDuties = DailyDutyLog::with(['monthlyDuty.vehicle', 'monthlyDuty.primaryDriver', 'directBooking.vehicle', 'directBooking.driver'])
            ->whereHas('monthlyDuty')
            ->whereDate('duty_date', now())
            ->get();

        $tomorrowsDuties = DailyDutyLog::with(['monthlyDuty.vehicle', 'monthlyDuty.primaryDriver', 'directBooking.vehicle', 'directBooking.driver'])
            ->whereHas('monthlyDuty')
            ->whereDate('duty_date', now()->addDay())
            ->get();

        return view('admin.dashboard', compact(
            'pendingDuties', 
            'completedDuties', 
            'todaysDuties',
            'tomorrowsDuties',
            'activeVehicles',
            'activeDrivers',
            'monthlyKm',
            'missingDuties'
        ));
    }
}
