<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyDutyLog;
use Illuminate\Http\Request;
use App\Rules\OdometerContinuityRule;

class DailyDutyLogController extends Controller
{
    protected $auditLogger;

    public function __construct(\App\Services\AuditLogService $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    public function index(Request $request)
    {
        $sortable = ['duty_date', 'status', 'total_km'];
        $sort = in_array($request->sort, $sortable) ? $request->sort : 'duty_date';
        $dir  = $request->dir === 'asc' ? 'asc' : 'desc';

        // Separate lists for the two independent filter dropdowns
        $departments = \App\Models\MonthlyDuty::distinct()->orderBy('department_name')->pluck('department_name');
        $officers    = \App\Models\MonthlyDuty::distinct()->orderBy('officer_name')->pluck('officer_name');

        $query = DailyDutyLog::with(['monthlyDuty.vehicle', 'monthlyDuty.primaryDriver', 'directBooking.vehicle', 'directBooking.driver']);

        if (!$request->filled('start_date') && !$request->filled('end_date')
            && !$request->filled('department') && !$request->filled('officer')) {
            $query->whereDate('duty_date', '<=', now()->toDateString());
        }

        // Filter by Department
        if ($request->filled('department')) {
            $query->whereHas('monthlyDuty', fn($q) => $q->where('department_name', $request->department));
        }

        // Filter by Officer
        if ($request->filled('officer')) {
            $query->whereHas('monthlyDuty', fn($q) => $q->where('officer_name', $request->officer));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('duty_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('duty_date', '<=', $request->end_date);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('monthlyDuty', function ($mq) use ($search) {
                    $mq->whereHas('vehicle', fn($vq) => $vq->where('vehicle_number', 'like', "%{$search}%"))
                       ->orWhereHas('primaryDriver', fn($dq) => $dq->where('name', 'like', "%{$search}%"));
                })->orWhereHas('directBooking', function ($bq) use ($search) {
                    $bq->whereHas('vehicle', fn($vq) => $vq->where('vehicle_number', 'like', "%{$search}%"))
                       ->orWhereHas('driver', fn($dq) => $dq->where('name', 'like', "%{$search}%"));
                });
            });
        }

        if ($request->export === 'csv') {
            return $this->exportCsv($query->orderBy($sort, $dir));
        }

        $logs = $query->orderBy($sort, $dir)->paginate(20)->withQueryString();
        return view('admin.daily-logs.index', compact('logs', 'departments', 'officers', 'sort', 'dir'));
    }

    private function exportCsv($query)
    {
        $filename = "daily_logs_" . date('Y-m-d') . ".csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        return response()->stream(function () use ($query) {
            $f = fopen('php://output', 'w');
            fputcsv($f, ['Date', 'Vehicle', 'Driver', 'Department/Customer', 'Status', 'Start Time', 'End Time', 'Start KM', 'End KM', 'Total KM']);
            
            foreach ($query->cursor() as $log) {
                fputcsv($f, [
                    $log->duty_date->toDateString(),
                    $log->monthlyDuty->vehicle->vehicle_number ?? ($log->directBooking->vehicle->vehicle_number ?? ''),
                    $log->monthlyDuty->primaryDriver->name ?? ($log->directBooking->driver->name ?? ''),
                    $log->monthlyDuty->department_name ?? ($log->directBooking->customer_name ?? ''),
                    $log->status, $log->start_time, $log->end_time,
                    $log->start_km, $log->end_km, $log->total_km,
                ]);
            }
            fclose($f);
        }, 200, $headers);
    }

    public function show(DailyDutyLog $log)
    {
        $log->load(['monthlyDuty.vehicle', 'monthlyDuty.primaryDriver', 'directBooking.vehicle', 'directBooking.driver', 'replacements']);
        return view('admin.daily-logs.show', compact('log'));
    }
    
    public function edit(DailyDutyLog $log)
    {
        // Only Admin and Operator can edit
        $this->authorize('update', $log);
        return view('admin.daily-logs.edit', compact('log'));
    }

    public function update(Request $request, DailyDutyLog $log)
    {
        $this->authorize('update', $log);

        $request->validate([
            'start_time' => 'nullable|date_format:H:i',
            'end_time'   => 'nullable|date_format:H:i',
            'start_km'   => ['nullable', 'integer', 'min:0', new OdometerContinuityRule($log->id)],
            'end_km'     => 'nullable|integer|min:0|gte:start_km',
            'total_km'   => 'nullable|integer|min:0',
            'status'     => 'required|in:pending,started,completed,missing,approved,disputed,replaced',
        ], [
            'status.required'        => 'Please select a status for this log.',
            'status.in'              => 'The selected status is invalid.',
            'start_km.integer'       => 'Start KM must be a whole number.',
            'start_km.min'           => 'Start KM cannot be negative.',
            'end_km.integer'         => 'End KM must be a whole number.',
            'end_km.min'             => 'End KM cannot be negative.',
            'end_km.gte'             => 'End KM must be greater than or equal to Start KM.',
            'start_time.date_format' => 'Start time must be in HH:MM format.',
            'end_time.date_format'   => 'End time must be in HH:MM format.',
        ]);

        $log->update($request->all());

        $this->auditLogger->log('Manual Update', 'daily_duty_logs', $log->id, "Updated by Admin/Operator");

        return redirect()->route('admin.daily-logs.show', $log->id)
            ->with('success', 'Log updated successfully.');
    }

    // update status (approve/dispute)
    public function updateStatus(Request $request, DailyDutyLog $log)
    {
        $request->validate([
            'status' => 'required|in:approved,disputed',
            'remarks' => 'nullable|string',
        ]);

        if (in_array($log->status, ['approved', 'disputed'])) {
            return back()->withErrors(['error' => 'Record is already locked and verified.']);
        }
        
        $log->update(['status' => $request->status]);
        
        $this->auditLogger->log('verify_status_' . $request->status, 'daily_duty_logs', $log->id, $request->remarks);
        
        return back()->with('success', 'Status updated.');
    }
}
