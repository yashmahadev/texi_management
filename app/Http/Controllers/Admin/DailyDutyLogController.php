<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyDutyLog;
use Illuminate\Http\Request;

class DailyDutyLogController extends Controller
{
    public function __construct()
    {
        // Protected by middleware in web.php
    }

    public function index(Request $request)
    {
        $query = DailyDutyLog::with(['monthlyDuty.vehicle', 'monthlyDuty.primaryDriver']);

        // Default: only records up to current date
        if (!$request->filled('start_date') && !$request->filled('end_date')) {
            $query->whereDate('duty_date', '<=', now()->toDateString());
        }

        // Filter by Date Range
        if ($request->filled('start_date')) {
            $query->whereDate('duty_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('duty_date', '<=', $request->end_date);
        }

        // Filter by Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by Search (Vehicle or Driver Name)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('monthlyDuty', function ($q) use ($search) {
                $q->whereHas('vehicle', function ($vq) use ($search) {
                    $vq->where('vehicle_number', 'like', "%{$search}%");
                })->orWhereHas('primaryDriver', function ($dq) use ($search) {
                    $dq->where('name', 'like', "%{$search}%");
                });
            });
        }

        $logs = $query->latest('duty_date')->paginate(20)->withQueryString();
            
        return view('admin.daily-logs.index', compact('logs'));
    }

    public function show(DailyDutyLog $log)
    {
        $log->load(['monthlyDuty', 'replacements']);
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
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'start_km' => 'nullable|integer',
            'end_km' => 'nullable|integer',
            'total_km' => 'nullable|integer',
            'status' => 'required|in:pending,started,completed,missing,approved,disputed,replaced',
        ]);

        $log->update($request->all());

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
        
        // Audit log? handled by service or manually here?
        // Better to use Service or creating AuditLog directly
        \App\Models\AuditLog::create([
            'entity_type' => 'daily_duty_logs',
            'entity_id' => $log->id,
            'action' => 'verify_status_' . $request->status,
            'performed_by' => \Illuminate\Support\Facades\Auth::id(),
            'remarks' => $request->remarks,
        ]);
        
        return back()->with('success', 'Status updated.');
    }
}
