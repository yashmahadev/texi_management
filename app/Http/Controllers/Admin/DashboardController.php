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

        // Base Query for Duties List (Applied Filters)
        $query = DailyDutyLog::with(['monthlyDuty.vehicle', 'monthlyDuty.primaryDriver'])
            ->whereHas('monthlyDuty');

        // Apply Status Filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Apply Search Filter (Vehicle, Driver, Officer, Department)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('monthlyDuty', function($q2) use ($search) {
                    $q2->where('officer_name', 'like', "%{$search}%")
                       ->orWhere('department_name', 'like', "%{$search}%")
                       ->orWhereHas('vehicle', function($q3) use ($search) {
                           $q3->where('vehicle_number', 'like', "%{$search}%");
                       })
                       ->orWhereHas('primaryDriver', function($q3) use ($search) {
                           $q3->where('name', 'like', "%{$search}%")
                             ->orWhere('mobile_number', 'like', "%{$search}%");
                       });
                });
            });
        }

        // Clone query for Today and Tomorrow
        $todaysDuties = (clone $query)->whereDate('duty_date', now())->get();
        $tomorrowsDuties = (clone $query)->whereDate('duty_date', now()->addDay())->get();

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
