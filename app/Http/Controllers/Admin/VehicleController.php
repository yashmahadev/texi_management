<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VehicleController extends Controller
{
    protected $whatsAppService;
    protected $auditLogger;

    public function __construct(WhatsAppService $whatsAppService, \App\Services\AuditLogService $auditLogger)
    {
        $this->whatsAppService = $whatsAppService;
        $this->auditLogger = $auditLogger;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Vehicle::class);
        $vehicles = Vehicle::with('driver')->latest()->paginate(10);
        return view('admin.vehicles.index', compact('vehicles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Vehicle::class);
        $types = config('taxi.vehicle_types');
        $drivers = Driver::where('status', 'active')->get();
        return view('admin.vehicles.create', compact('types', 'drivers'));
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
            'puc_expiry_date' => 'nullable|date',
            'status' => 'required|in:active,inactive,maintenance',
            
            // Driver Fields (Mandatory)
            'driver_name' => 'required|string|max:255',
            'mobile_number' => 'required|string|max:10|unique:drivers,mobile_number',
            'driving_licence_number' => 'nullable|string|max:50',
            'driving_licence_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'aadhaar_number' => 'nullable|string|max:20',
            'aadhaar_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'alternate_contact_number' => 'nullable|string|max:15',
            'relationship_with_alternate_contact' => 'nullable|string|max:50',
            'state' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',

            // Owner Logic
            'is_driver_owner' => 'boolean',
            'owner_name' => 'required_if:is_driver_owner,0|nullable|string|max:255',
            'owner_mobile' => 'required_if:is_driver_owner,0|nullable|string|max:10',
            'owner_aadhaar_number' => 'nullable|string|max:20',
            'owner_pancard_number' => 'nullable|string|max:20',
        ]);

        try {
            DB::beginTransaction();

            // 1. Create Driver
            $driverData = [
                'name' => $request->driver_name,
                'mobile_number' => $request->mobile_number,
                'driving_licence_number' => $request->driving_licence_number,
                'aadhaar_number' => $request->aadhaar_number,
                'alternate_contact_number' => $request->alternate_contact_number,
                'relationship_with_alternate_contact' => $request->relationship_with_alternate_contact,
                'state' => $request->state,
                'city' => $request->city,
                'pincode' => $request->pincode,
                'status' => 'active',
            ];

            if ($request->hasFile('driving_licence_document')) {
                $driverData['driving_licence_document'] = $request->file('driving_licence_document')->store('documents/dl', 'public');
            }
            if ($request->hasFile('aadhaar_document')) {
                $driverData['aadhaar_document'] = $request->file('aadhaar_document')->store('documents/aadhaar', 'public');
            }

            $driver = Driver::create($driverData);
            
            // Send WhatsApp Welcome Message
            try {
                $this->whatsAppService->sendNotification($driver->mobile_number, 'registration_welcome', [
                    '1' => $driver->name,
                    '2' => 'Driver'
                ]);
            } catch (\Exception $e) {
                \Log::warning('WhatsApp failed: ' . $e->getMessage());
            }

            // 2. Prepare Owner Info
            $isDriverOwner = $request->boolean('is_driver_owner', true);
            $ownerName = $isDriverOwner ? $driver->name : $request->owner_name;
            $ownerMobile = $isDriverOwner ? $driver->mobile_number : $request->owner_mobile;
            $ownerAadhaar = $isDriverOwner ? $driver->aadhaar_number : $request->owner_aadhaar_number;
            $ownerPan = $request->owner_pancard_number;

            // 3. Create Vehicle
            $vehicle = Vehicle::create([
                'vehicle_number' => $request->vehicle_number,
                'vehicle_type' => $request->vehicle_type,
                'puc_expiry_date' => $request->puc_expiry_date,
                'status' => $request->status,
                'driver_id' => $driver->id,
                'is_driver_owner' => $isDriverOwner,
                'owner_name' => $ownerName,
                'owner_mobile' => $ownerMobile,
                'owner_aadhaar_number' => $ownerAadhaar,
                'owner_pancard_number' => $ownerPan,
            ]);

            $this->auditLogger->log('Create Vehicle', 'vehicles', $vehicle->id, "Created vehicle {$vehicle->vehicle_number} with driver {$driver->name}");

            DB::commit();

            return redirect()->route('admin.vehicles.index')
                ->with('success', 'Vehicle and Driver registered successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Error occurred: ' . $e->getMessage()]);
        }
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
            'puc_expiry_date' => 'nullable|date',
            'status' => 'required|in:active,inactive,maintenance',
            
            // Driver Details (Must be associated)
            'driver_name' => 'required|string|max:255',
            'mobile_number' => 'required|string|max:10|unique:drivers,mobile_number,' . $vehicle->driver_id,
            'driving_licence_number' => 'nullable|string|max:50',
            'driving_licence_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'aadhaar_number' => 'nullable|string|max:20',
            'aadhaar_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'alternate_contact_number' => 'nullable|string|max:15',
            'relationship_with_alternate_contact' => 'nullable|string|max:50',
            'state' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',

            // Owner Logic
            'is_driver_owner' => 'boolean',
            'owner_name' => 'required_if:is_driver_owner,0|nullable|string|max:255',
            'owner_mobile' => 'required_if:is_driver_owner,0|nullable|string|max:10',
            'owner_aadhaar_number' => 'nullable|string|max:20',
            'owner_pancard_number' => 'nullable|string|max:20',
        ]);

        try {
            DB::beginTransaction();

            $driver = $vehicle->driver;
            $driverData = [
                'name' => $request->driver_name,
                'mobile_number' => $request->mobile_number,
                'driving_licence_number' => $request->driving_licence_number,
                'aadhaar_number' => $request->aadhaar_number,
                'alternate_contact_number' => $request->alternate_contact_number,
                'relationship_with_alternate_contact' => $request->relationship_with_alternate_contact,
                'state' => $request->state,
                'city' => $request->city,
                'pincode' => $request->pincode,
            ];

            if ($request->hasFile('driving_licence_document')) {
                if ($driver->driving_licence_document) {
                    Storage::disk('public')->delete($driver->driving_licence_document);
                }
                $driverData['driving_licence_document'] = $request->file('driving_licence_document')->store('documents/dl', 'public');
            }
            if ($request->hasFile('aadhaar_document')) {
                if ($driver->aadhaar_document) {
                    Storage::disk('public')->delete($driver->aadhaar_document);
                }
                $driverData['aadhaar_document'] = $request->file('aadhaar_document')->store('documents/aadhaar', 'public');
            }

            $driver->update($driverData);

            // Update Vehicle & Owner details
            $isDriverOwner = $request->boolean('is_driver_owner', true);
            $ownerName = $isDriverOwner ? $driver->name : $request->owner_name;
            $ownerMobile = $isDriverOwner ? $driver->mobile_number : $request->owner_mobile;
            $ownerAadhaar = $isDriverOwner ? $driver->aadhaar_number : $request->owner_aadhaar_number;
            $ownerPan = $request->owner_pancard_number;

            $vehicle->update([
                'vehicle_number' => $request->vehicle_number,
                'vehicle_type' => $request->vehicle_type,
                'puc_expiry_date' => $request->puc_expiry_date,
                'status' => $request->status,
                'is_driver_owner' => $isDriverOwner,
                'owner_name' => $ownerName,
                'owner_mobile' => $ownerMobile,
                'owner_aadhaar_number' => $ownerAadhaar,
                'owner_pancard_number' => $ownerPan,
            ]);

            $this->auditLogger->log('Update Vehicle', 'vehicles', $vehicle->id, "Updated details for {$vehicle->vehicle_number}");

            DB::commit();

            return redirect()->route('admin.vehicles.index')
                ->with('success', 'Vehicle and Driver updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Error occurred: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vehicle $vehicle)
    {
        $this->authorize('delete', $vehicle);
        
        if ($vehicle->monthlyDuties()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete vehicle with associated duties.']);
        }

        $vehicleNumber = $vehicle->vehicle_number;
        $vehicleId = $vehicle->id;
        $vehicle->delete();

        $this->auditLogger->log('Delete Vehicle', 'vehicles', $vehicleId, "Deleted vehicle {$vehicleNumber}");

        return redirect()->route('admin.vehicles.index')
            ->with('success', 'Vehicle deleted successfully.');
    }
}
