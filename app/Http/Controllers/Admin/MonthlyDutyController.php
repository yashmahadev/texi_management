<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\MonthlyDuty;
use App\Models\Vehicle;
use App\Services\DutyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MonthlyDutyController extends Controller
{
    protected $dutyService;

    public function __construct(DutyService $dutyService)
    {
        $this->dutyService = $dutyService;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', MonthlyDuty::class);

        $sortable = ['id', 'start_date', 'end_date', 'department_name', 'officer_name', 'created_at'];
        $sort = in_array($request->sort, $sortable) ? $request->sort : 'created_at';
        $dir  = $request->dir === 'asc' ? 'asc' : 'desc';

        $query = MonthlyDuty::with(['vehicle', 'primaryDriver']);

        if ($request->filled('department')) {
            $query->where('department_name', 'like', '%' . $request->department . '%');
        }
        if ($request->filled('officer')) {
            $query->where('officer_name', 'like', '%' . $request->officer . '%');
        }
        if ($request->filled('start_date')) {
            $query->whereDate('start_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('end_date', '<=', $request->end_date);
        }

        // CSV export
        if ($request->export === 'csv') {
            return $this->exportCsv($query->orderBy($sort, $dir)->get());
        }

        $duties = $query->orderBy($sort, $dir)->paginate(10)->withQueryString();
        return view('admin.monthly-duties.index', compact('duties', 'sort', 'dir'));
    }

    private function exportCsv($duties)
    {
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="monthly_duties.csv"'];
        $callback = function () use ($duties) {
            $f = fopen('php://output', 'w');
            fputcsv($f, ['ID', 'Department', 'Officer', 'Vehicle', 'Driver', 'Start Date', 'End Date', 'Start Time', 'State', 'City', 'Recurrence', 'Created At']);
            foreach ($duties as $d) {
                fputcsv($f, [
                    $d->id, $d->department_name, $d->officer_name,
                    $d->vehicle->vehicle_number ?? '', $d->primaryDriver->name ?? '',
                    $d->start_date->toDateString(), $d->end_date->toDateString(),
                    $d->expected_start_time, $d->state, $d->city,
                    $d->recurrence_type, $d->created_at->toDateTimeString(),
                ]);
            }
            fclose($f);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function create()
    {
        $this->authorize('create', MonthlyDuty::class);
        $types = config('taxi.vehicle_types');
        $vehicles = []; 
        $groups = ['Government', 'Corporate'];
        $officers = MonthlyDuty::distinct()->pluck('officer_name');
        
        return view('admin.monthly-duties.create', compact('types', 'vehicles', 'groups', 'officers'));
    }

    public function store(\App\Http\Requests\CreateMonthlyDutyRequest $request)
    {
        $this->authorize('create', MonthlyDuty::class);
        $result = $this->dutyService->createMonthlyDuty($request->validated(), Auth::id());

        // Build success message — tell user how many duties were created
        $totalCreated = $result['total_created'] ?? 1;
        $message = $totalCreated > 1
            ? "Monthly duty created successfully with {$totalCreated} recurrences (total {$totalCreated} duties generated)."
            : 'Monthly duty created successfully.';

        if ($request->input('action') === 'save_and_create') {
            return redirect()->route('admin.monthly-duties.create')->with('success', $message . ' You can create another one now.');
        }

        return redirect()->route('admin.monthly-duties.index')->with('success', $message);
    }

    public function show(MonthlyDuty $monthlyDuty)
    {
        $this->authorize('view', $monthlyDuty);
        $monthlyDuty->load(['dailyLogs', 'vehicle', 'primaryDriver']);
        return view('admin.monthly-duties.show', compact('monthlyDuty'));
    }

    public function edit(MonthlyDuty $monthlyDuty)
    {
        $this->authorize('update', $monthlyDuty);
        $monthlyDuty->load(['vehicle', 'primaryDriver', 'department']);
        $groups  = ['Government', 'Corporate'];
        $types   = config('taxi.vehicle_types');
        $officers = MonthlyDuty::distinct()->orderBy('officer_name')->pluck('officer_name');
        return view('admin.monthly-duties.edit', compact('monthlyDuty', 'groups', 'types', 'officers'));
    }

    public function update(Request $request, MonthlyDuty $monthlyDuty)
    {
        $this->authorize('update', $monthlyDuty);

        $request->validate([
            'group'               => 'required|in:Government,Corporate',
            'department_id'       => 'required|exists:departments,id',
            'officer_name'        => 'required|string|min:2|max:255',
            'vehicle_id'          => [
                'required',
                'exists:vehicles,id',
                function ($attribute, $value, $fail) use ($monthlyDuty, $request) {
                    $startDate = $request->input('start_date');
                    $endDate   = $request->input('end_date');
                    if ($startDate && $endDate) {
                        $overlap = MonthlyDuty::where('vehicle_id', $value)
                            ->where('id', '!=', $monthlyDuty->id) // exclude current duty
                            ->where(function ($q) use ($startDate, $endDate) {
                                $q->whereBetween('start_date', [$startDate, $endDate])
                                  ->orWhereBetween('end_date', [$startDate, $endDate])
                                  ->orWhere(function ($q2) use ($startDate, $endDate) {
                                      $q2->where('start_date', '<=', $startDate)
                                         ->where('end_date', '>=', $endDate);
                                  });
                            })->exists();
                        if ($overlap) {
                            $fail('This vehicle is already assigned to another duty during the selected period.');
                        }
                    }
                },
            ],
            'start_date'          => 'required|date',
            'end_date'            => 'required|date|after_or_equal:start_date',
            'expected_start_time' => 'required|date_format:H:i',
            'expected_end_time'   => 'nullable|date_format:H:i',
            'state'               => 'nullable|string|max:100',
            'city'                => 'nullable|string|max:100',
            'pincode'             => 'nullable|digits:6',
            'route_remarks'       => 'nullable|string|max:1000',
            'is_recurring'        => 'nullable|boolean',
        ], [
            'group.required'                  => 'Please select a group.',
            'group.in'                        => 'Group must be Government or Corporate.',
            'department_id.required'          => 'Please select a department.',
            'department_id.exists'            => 'The selected department is invalid.',
            'officer_name.required'           => 'Officer name is required.',
            'officer_name.min'                => 'Officer name must be at least 2 characters.',
            'vehicle_id.required'             => 'Please select a vehicle.',
            'vehicle_id.exists'               => 'The selected vehicle does not exist.',
            'start_date.required'             => 'Start date is required.',
            'end_date.required'               => 'End date is required.',
            'end_date.after_or_equal'         => 'End date must be on or after the start date.',
            'expected_start_time.required'    => 'Expected start time is required.',
            'expected_start_time.date_format' => 'Start time must be in HH:MM format.',
            'expected_end_time.date_format'   => 'End time must be in HH:MM format.',
            'pincode.digits'                  => 'Pincode must be exactly 6 digits.',
        ]);

        $this->dutyService->updateMonthlyDuty($monthlyDuty, $request->all());

        return redirect()->route('admin.monthly-duties.show', $monthlyDuty)
            ->with('success', 'Monthly duty updated successfully.');
    }

    public function getVehiclesByType(Request $request)
    {
        $type      = $request->type;
        $startDate = $request->start_date;
        $endDate   = $request->end_date;
        $excludeId = $request->exclude_duty_id; // exclude current duty when editing

        $vehicles = Vehicle::with('driver')
            ->where('status', 'active')
            ->where('vehicle_type', $type)
            ->get();

        $overlappingVehicleIds = [];
        if ($startDate && $endDate) {
            $query = MonthlyDuty::where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(function ($q2) use ($startDate, $endDate) {
                      $q2->where('start_date', '<=', $startDate)
                         ->where('end_date', '>=', $endDate);
                  });
            });
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            $overlappingVehicleIds = $query->pluck('vehicle_id')->toArray();
        }

        $data = $vehicles->map(function ($v) use ($overlappingVehicleIds) {
            return [
                'id'            => $v->id,
                'vehicle_number' => $v->vehicle_number,
                'driver_name'   => $v->driver->name ?? 'No Driver',
                'driver_mobile' => $v->driver->mobile_number ?? '',
                'is_assigned'   => in_array($v->id, $overlappingVehicleIds),
            ];
        });

        return response()->json($data);
    }

    public function destroy(MonthlyDuty $monthlyDuty)
    {
        $this->authorize('delete', $monthlyDuty);

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // 0. Notify Driver before deletion
            if ($monthlyDuty->primaryDriver) {
                // WhatsApp Cancellation
                $whatsapp = app(\App\Services\WhatsAppService::class);
                $whatsapp->sendDutyCancellation($monthlyDuty->primaryDriver->mobile_number, (string)$monthlyDuty->id);

                // FCM Cancellation
                if ($monthlyDuty->primaryDriver->fcm_token) {
                    $notificationService = app(\App\Services\NotificationService::class);
                    $notificationService->sendNotification(
                        $monthlyDuty->primaryDriver->fcm_token,
                        "🛑 Duty Cancelled",
                        "Your duty for {$monthlyDuty->department_name} has been cancelled.",
                        ['link' => route('driver.dashboard')]
                    );
                }
            }

            // Get all log IDs for this duty
            $logIds = $monthlyDuty->dailyLogs()->pluck('id');

            // 1. Delete Replacements
            \App\Models\DutyReplacement::whereIn('daily_duty_log_id', $logIds)->delete();

            // 2. Delete Billing Logs
            \App\Models\BillingLog::whereIn('daily_duty_log_id', $logIds)->delete();

            // 3. Delete Daily Logs
            $monthlyDuty->dailyLogs()->delete();

            // 4. Delete Monthly Duty
            $monthlyDuty->delete();

            \Illuminate\Support\Facades\DB::commit();

            return redirect()->route('admin.monthly-duties.index')
                ->with('success', 'Monthly duty and all associated records deleted successfully.');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->withErrors(['error' => 'Failed to delete: ' . $e->getMessage()]);
        }
    }
}
