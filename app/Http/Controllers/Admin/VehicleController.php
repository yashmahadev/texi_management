<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function __construct()
    {
        // Protected by manual authorize calls
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Vehicle::class);
        $vehicles = Vehicle::latest()->paginate(10);
        return view('admin.vehicles.index', compact('vehicles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Vehicle::class);
        $types = config('taxi.vehicle_types');
        return view('admin.vehicles.create', compact('types'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Vehicle::class);
        $request->validate([
            'vehicle_number' => 'required|string|unique:vehicles,vehicle_number|max:20',
            'vehicle_type' => 'required|string|in:' . implode(',', config('taxi.vehicle_types')),
            'status' => 'required|in:active,inactive,maintenance',
        ]);

        Vehicle::create($request->all());

        return redirect()->route('admin.vehicles.index')
            ->with('success', 'Vehicle created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Vehicle $vehicle)
    {
        $this->authorize('update', $vehicle);
        $types = config('taxi.vehicle_types');
        return view('admin.vehicles.edit', compact('vehicle', 'types'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Vehicle $vehicle)
    {
        $this->authorize('update', $vehicle);
        $request->validate([
            'vehicle_number' => 'required|string|max:20|unique:vehicles,vehicle_number,' . $vehicle->id,
            'vehicle_type' => 'required|string|in:' . implode(',', config('taxi.vehicle_types')),
            'status' => 'required|in:active,inactive,maintenance',
        ]);

        $vehicle->update($request->all());

        return redirect()->route('admin.vehicles.index')
            ->with('success', 'Vehicle updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vehicle $vehicle)
    {
        $this->authorize('delete', $vehicle);
        // Check if vehicle has any duties associated
        if ($vehicle->monthlyDuties()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete vehicle with associated duties.']);
        }

        $vehicle->delete();

        return redirect()->route('admin.vehicles.index')
            ->with('success', 'Vehicle deleted successfully.');
    }
}
