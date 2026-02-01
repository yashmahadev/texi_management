<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\DailyDutyLog;
use App\Services\DutyService;
use Illuminate\Http\Request;

class DutyController extends Controller
{
    protected $dutyService;

    public function __construct(DutyService $dutyService)
    {
        $this->dutyService = $dutyService;
    }

    public function start(Request $request, DailyDutyLog $log)
    {
        if ($log->duty_date->format('Y-m-d') !== now()->format('Y-m-d')) {
            return response()->json(['error' => 'Can only start duty for today.'], 400);
        }

        if ($log->status !== 'pending') {
            return response()->json(['error' => 'Duty has already been ' . $log->status . '.'], 400);
        }
        
        $request->validate([
            'start_km' => 'required|integer|min:0',
            'photo' => 'nullable|image|max:10240',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('duty_photos', 'public');
        }

        $this->dutyService->startDuty($log, $request->all(), $photoPath);

        return response()->json([
            'success' => true,
            'message' => 'Duty started successfully.',
            'log' => $log->fresh()
        ]);
    }

    public function end(Request $request, DailyDutyLog $log)
    {
        if ($log->duty_date->format('Y-m-d') !== now()->format('Y-m-d')) {
            return response()->json(['error' => 'Can only end duty for today.'], 400);
        }

        if ($log->status !== 'started') {
            return response()->json(['error' => 'Duty cannot be ended (Current status: ' . $log->status . ').'], 400);
        }

        $request->validate([
            'end_km' => 'required|integer|gte:' . $log->start_km,
            'photo' => 'nullable|image|max:10240',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('duty_photos', 'public');
        }

        $this->dutyService->endDuty($log, $request->all(), $photoPath);

        return response()->json([
            'success' => true,
            'message' => 'Duty ended successfully.',
            'log' => $log->fresh()
        ]);
    }

    public function history(Request $request)
    {
        $driverId = $request->user()->id;
        
        $logs = DailyDutyLog::where(function($query) use ($driverId) {
            $query->whereHas('monthlyDuty', function($q) use ($driverId) {
                $q->where('primary_driver_id', $driverId);
            })
            ->orWhereHas('replacements', function($q) use ($driverId) {
                $q->where('replacement_driver_id', $driverId);
            });
        })
        ->whereDate('duty_date', '<=', now()->toDateString())
        ->latest('duty_date')
        ->with(['monthlyDuty.vehicle'])
        ->paginate(15);

        return response()->json($logs);
    }
}
