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
        $pendingTodayQuery = DailyDutyLog::whereDate('duty_date', now());
        $pendingDuties = (clone $pendingTodayQuery)->where('status', 'pending')->count();
        $completedDuties = (clone $pendingTodayQuery)->where('status', 'completed')->count();

        $activeVehicles = \App\Models\Vehicle::where('status', 'active')->count();
        $activeDrivers = \App\Models\Driver::where('status', 'active')->count();

        // Monthly Stats
        $currentMonthStart = now()->startOfMonth();
        $monthlyKm = DailyDutyLog::whereDate('duty_date', '>=', $currentMonthStart)
            ->sum('total_km');
        $missingDuties = DailyDutyLog::where('status', 'missing')->count();

        // Common query logic for filtered tables
        $applyFilters = function ($query) use ($request) {
            $query->with(['monthlyDuty.vehicle', 'monthlyDuty.primaryDriver', 'directBooking.vehicle', 'directBooking.driver']);
            
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->whereHas('monthlyDuty.vehicle', fn($vq) => $vq->where('vehicle_number', 'like', "%{$search}%"))
                      ->orWhereHas('monthlyDuty.primaryDriver', fn($dq) => $dq->where('name', 'like', "%{$search}%"))
                      ->orWhereHas('directBooking.vehicle', fn($vq) => $vq->where('vehicle_number', 'like', "%{$search}%"))
                      ->orWhereHas('directBooking.driver', fn($dq) => $dq->where('name', 'like', "%{$search}%"))
                      ->orWhereHas('directBooking', fn($bq) => $bq->where('customer_name', 'like', "%{$search}%"))
                      ->orWhereHas('monthlyDuty', fn($mq) => $mq->where('officer_name', 'like', "%{$search}%"));
                });
            }
            
            return $query->orderBy('status', 'asc')->get();
        };

        // Pending Duties Tables
        $todaysDuties = $applyFilters(DailyDutyLog::whereDate('duty_date', now()));
        $tomorrowsDuties = $applyFilters(DailyDutyLog::whereDate('duty_date', now()->addDay()));

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
