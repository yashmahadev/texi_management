<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;

class DriverController extends Controller
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
        $this->authorize('viewAny', Driver::class);
        $drivers = Driver::latest()->paginate(10);
        return view('admin.drivers.index', compact('drivers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Driver::class);
        return view('admin.drivers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Driver::class);
        $request->validate([
            'name' => 'required|string|max:255',
            'mobile_number' => 'required|string|size:10|unique:drivers,mobile_number',
            'status' => 'required|in:active,inactive,suspended',
        ]);

        Driver::create($request->all());

        return redirect()->route('admin.drivers.index')
            ->with('success', 'Driver created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Driver $driver)
    {
        $this->authorize('update', $driver);
        return view('admin.drivers.edit', compact('driver'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Driver $driver)
    {
        $this->authorize('update', $driver);
        $request->validate([
            'name' => 'required|string|max:255',
            'mobile_number' => 'required|string|size:10|unique:drivers,mobile_number,' . $driver->id,
            'status' => 'required|in:active,inactive,suspended',
        ]);

        $driver->update($request->all());

        return redirect()->route('admin.drivers.index')
            ->with('success', 'Driver updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Driver $driver)
    {
        $this->authorize('delete', $driver);
        // Check for dependencies
        // 1. Monthly Duties as Primary
        if ($driver->monthlyDuties()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete driver associated with monthly duties.']);
        }
        
        // 2. Replacements as Original or Replacement
        if ($driver->replacementsAsOriginal()->exists() || $driver->replacementsAsReplacement()->exists()) {
             return back()->withErrors(['error' => 'Cannot delete driver associated with duty replacements.']);
        }

        $driver->delete();

        return redirect()->route('admin.drivers.index')
            ->with('success', 'Driver deleted successfully.');
    }
}
