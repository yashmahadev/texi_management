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

        $duties = $query->latest()->paginate(10)->withQueryString();
        return view('admin.monthly-duties.index', compact('duties'));
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
        $this->dutyService->createMonthlyDuty($request->validated(), Auth::id());

        if ($request->input('action') === 'save_and_create') {
            return redirect()->route('admin.monthly-duties.create')->with('success', 'Monthly duty created. You can create another one now.');
        }

        return redirect()->route('admin.monthly-duties.index')->with('success', 'Monthly duty created successfully.');
    }

    public function show(MonthlyDuty $monthlyDuty)
    {
        $this->authorize('view', $monthlyDuty);
        $monthlyDuty->load(['dailyLogs', 'vehicle', 'primaryDriver']);
        return view('admin.monthly-duties.show', compact('monthlyDuty'));
    }

    public function getVehiclesByType(Request $request)
    {
        $type = $request->type;
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $vehicles = Vehicle::with('driver')
            ->where('status', 'active')
            ->where('vehicle_type', $type)
            ->get();

        // Check for overlaps if dates are provided
        $overlappingVehicleIds = [];
        if ($startDate && $endDate) {
            $overlappingVehicleIds = MonthlyDuty::where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                      ->orWhereBetween('end_date', [$startDate, $endDate])
                      ->orWhere(function ($q) use ($startDate, $endDate) {
                          $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                      });
            })->pluck('vehicle_id')->toArray();
        }
            
        $data = $vehicles->map(function($v) use ($overlappingVehicleIds) {
            return [
                'id' => $v->id,
                'vehicle_number' => $v->vehicle_number,
                'driver_name' => $v->driver->name ?? 'No Driver',
                'driver_mobile' => $v->driver->mobile_number ?? '',
                'is_assigned' => in_array($v->id, $overlappingVehicleIds)
            ];
        });

        return response()->json($data);
    }
}
