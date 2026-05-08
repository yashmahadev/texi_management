<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\DailyDutyLog;
use App\Rules\OdometerContinuityRule;
use App\Services\DutyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DutyController extends Controller
{
    protected $dutyService;

    public function __construct(DutyService $dutyService)
    {
        $this->dutyService = $dutyService;
    }

    public function start(Request $request, DailyDutyLog $log)
    {
        // Validation: Log belongs to driver (check Primary or Replacement)
        // Check date is today
        if ($log->duty_date->format('Y-m-d') !== now()->format('Y-m-d')) {
            return back()->withErrors(['error' => 'Can only start duty for today.']);
        }

        if ($log->status !== 'pending') {
            return back()->withErrors(['error' => 'Duty has already been ' . $log->status . '.']);
        }
        
        $request->validate([
            'start_km' => ['required', 'integer', 'min:0', new OdometerContinuityRule($log->id)],
            'photo' => 'nullable|image|max:10240', // 10MB
        ], [
            'start_km.required' => 'Start KM is required.',
            'start_km.integer' => 'Start KM must be a whole number.',
            'start_km.min' => 'Start KM cannot be negative.',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('duty_photos', 'public');
        }

        $this->dutyService->startDuty($log, $request->all(), $photoPath);

        return redirect()->route('driver.dashboard')->with('success', 'Duty started successfully.');
    }

    public function end(Request $request, DailyDutyLog $log)
    {
        if ($log->duty_date->format('Y-m-d') !== now()->format('Y-m-d')) {
            return back()->withErrors(['error' => 'Can only end duty for today.']);
        }

        if ($log->status !== 'started') {
            return back()->withErrors(['error' => 'Duty cannot be ended (Current status: ' . $log->status . ').']);
        }

        $request->validate([
            'end_km' => 'required|integer|gte:' . $log->start_km,
            'photo' => 'nullable|image|max:10240',
        ], [
            'end_km.required' => 'End KM is required.',
            'end_km.integer' => 'End KM must be a whole number.',
            'end_km.gte' => 'End KM must be greater than or equal to Start KM (' . $log->start_km . ').',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('duty_photos', 'public');
        }

        $this->dutyService->endDuty($log, $request->all(), $photoPath);

        return redirect()->route('driver.dashboard')->with('success', 'Duty ended successfully.');
    }

    public function history()
    {
        $driverId = auth()->guard('driver')->id();
        
        $logs = DailyDutyLog::where(function($query) use ($driverId) {
            $query->whereHas('monthlyDuty', function($q) use ($driverId) {
                $q->where('primary_driver_id', $driverId);
            })
            ->orWhereHas('replacements', function($q) use ($driverId) {
                $q->where('replacement_driver_id', $driverId);
            })
            ->orWhereHas('directBooking', function($q) use ($driverId) {
                $q->where('driver_id', $driverId);
            });
        })
        ->whereDate('duty_date', '<=', now()->toDateString())
        ->latest('duty_date')
        ->paginate(15);

        return view('driver.history', compact('logs'));
    }
}
