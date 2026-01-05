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

    public function index()
    {
        $this->authorize('viewAny', MonthlyDuty::class);
        $duties = MonthlyDuty::with(['vehicle', 'primaryDriver'])->latest()->paginate(10);
        return view('admin.monthly-duties.index', compact('duties'));
    }

    public function create()
    {
        $this->authorize('create', MonthlyDuty::class);
        $drivers = Driver::where('status', 'active')->get();
        $types = config('taxi.vehicle_types');
        // Initial vehicles for 'all' or first type?
        // Let's pass empty vehicles first, they will be loaded via AJAX or 
        // we can pass all and filter in JS if the list is small. 
        // But user asked for dependent dropdown logic.
        $vehicles = []; 
        return view('admin.monthly-duties.create', compact('drivers', 'types', 'vehicles'));
    }

    public function store(\App\Http\Requests\CreateMonthlyDutyRequest $request)
    {
        $this->authorize('create', MonthlyDuty::class);
        $this->dutyService->createMonthlyDuty($request->validated(), Auth::id());

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
        $vehicles = Vehicle::where('status', 'active')
            ->where('vehicle_type', $type)
            ->get(['id', 'vehicle_number']);
            
        return response()->json($vehicles);
    }
}
