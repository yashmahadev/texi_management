<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\MonthlyDuty;
use App\Models\Vehicle;
use App\Services\DutyService;
use Illuminate\Http\Request;
use App\Http\Requests\UpdateMonthlyDutyRequest;
use Illuminate\Support\Facades\Auth;
use App\Jobs\SendWhatsAppNotification;
use App\Jobs\SendFcmNotification;

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

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('department_name', 'like', "%{$search}%")
                  ->orWhere('officer_name', 'like', "%{$search}%")
                  ->orWhereHas('vehicle', fn($vq) => $vq->where('vehicle_number', 'like', "%{$search}%"));
            });
        }

        // CSV export
        if ($request->export === 'csv') {
            return $this->exportCsv($query->orderBy($sort, $dir));
        }

        $duties = $query->orderBy($sort, $dir)->paginate(10)->withQueryString();
        return view('admin.monthly-duties.index', compact('duties', 'sort', 'dir'));
    }

    private function exportCsv($query)
    {
        $filename = "monthly_duties_" . date('Y-m-d') . ".csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        return response()->stream(function () use ($query) {
            $f = fopen('php://output', 'w');
            fputcsv($f, ['ID', 'Department', 'Officer', 'Vehicle', 'Driver', 'Start Date', 'End Date', 'Start Time', 'State', 'City', 'Recurrence', 'Created At']);

            foreach ($query->cursor() as $d) {
                fputcsv($f, [
                    $d->id, $d->department_name, $d->officer_name,
                    $d->vehicle->vehicle_number ?? '', $d->primaryDriver->name ?? '',
                    $d->start_date->toDateString(), $d->end_date->toDateString(),
                    $d->expected_start_time, $d->state, $d->city,
                    $d->is_recurring ? 'Yes' : 'No', $d->created_at->toDateTimeString(),
                ]);
            }
            fclose($f);
        }, 200, $headers);
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

    public function update(UpdateMonthlyDutyRequest $request, MonthlyDuty $monthlyDuty)
    {
        $this->authorize('update', $monthlyDuty);

        $this->dutyService->updateMonthlyDuty($monthlyDuty, $request->validated());

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
                SendWhatsAppNotification::dispatch(
                    $monthlyDuty->primaryDriver->mobile_number,
                    'duty_cancelled',
                    ["1" => (string)$monthlyDuty->id]
                );

                // FCM Cancellation
                if ($monthlyDuty->primaryDriver->fcm_token) {
                    SendFcmNotification::dispatch(
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
