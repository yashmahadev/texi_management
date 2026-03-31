<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\Owner;
use App\Services\WhatsAppService;
use App\Services\FcmService;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VehicleController extends Controller
{
    protected $whatsAppService;
    protected $auditLogger;
    protected $notificationService;

    public function __construct(
        WhatsAppService $whatsAppService, 
        AuditLogService $auditLogger, 
        \App\Services\NotificationService $notificationService
    ) {
        $this->whatsAppService = $whatsAppService;
        $this->auditLogger = $auditLogger;
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Vehicle::class);

        $sortable = ['vehicle_number', 'vehicle_type', 'status', 'created_at'];
        $sort = in_array($request->sort, $sortable) ? $request->sort : 'created_at';
        $dir  = $request->dir === 'asc' ? 'asc' : 'desc';

        $query = Vehicle::with(['driver', 'owner'])->orderBy($sort, $dir);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('vehicle_number', 'LIKE', "%{$search}%")
                  ->orWhereHas('driver', function($dq) use ($search) {
                      $dq->where('name', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('owner', function($oq) use ($search) {
                      $oq->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Type Filter
        if ($request->filled('vehicle_type')) {
            $query->where('vehicle_type', $request->vehicle_type);
        }

        // Fuel Type Filter
        if ($request->filled('fuel_type')) {
            $query->where('fuel_type', $request->fuel_type);
        }

        // Status Filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $vehicles = $query->paginate(15)->withQueryString();
        
        $types = config('taxi.vehicle_types');

        // CSV export
        if ($request->export === 'csv') {
            return $this->exportCsv($query->get());
        }

        return view('admin.vehicles.index', compact('vehicles', 'types', 'sort', 'dir'));
    }

    private function exportCsv($vehicles)
    {
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="vehicles.csv"'];
        $callback = function () use ($vehicles) {
            $f = fopen('php://output', 'w');
            fputcsv($f, ['Vehicle #', 'Type', 'Fuel', 'Make/Model', 'Status', 'Driver', 'Driver Mobile', 'DL Number', 'DL Expiry', 'PUC Expiry', 'Insurance Expiry', 'Owner']);
            foreach ($vehicles as $v) {
                fputcsv($f, [
                    $v->vehicle_number, $v->vehicle_type, $v->fuel_type, $v->make_model, $v->status,
                    $v->driver->name ?? '', $v->driver->mobile_number ?? '',
                    $v->driver->driving_licence_number ?? '',
                    $v->driver->dl_expiry ? \Carbon\Carbon::parse($v->driver->dl_expiry)->toDateString() : '',
                    $v->puc_expiry_date?->toDateString(), $v->insurance_expiry_date?->toDateString(),
                    $v->owner->name ?? '',
                ]);
            }
            fclose($f);
        };
        return response()->stream($callback, 200, $headers);
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
            'owner_name'   => 'required|string|max:255',
            'owner_mobile' => ['required', 'string', 'regex:/^[6-9]\d{9}$/', 'unique:owners,mobile'],
            'owner_aadhaar_number' => 'nullable|digits:12|unique:owners,aadhaar_number',
            'owner_pancard_number' => 'nullable|string|max:10|unique:owners,pancard_number',
            'owner_address' => 'nullable|string|max:500',

            'vehicles'   => 'required|array|min:1',
            'vehicles.*.vehicle_number'       => 'required|string|max:20|unique:vehicles,vehicle_number',
            'vehicles.*.make_model'           => 'required|string|max:255',
            'vehicles.*.fuel_type'            => 'required|string',
            'vehicles.*.transmission_type'    => 'required|string',
            'vehicles.*.vehicle_type'         => 'required|string',
            'vehicles.*.vehicle_type_custom'  => 'required_if:vehicles.*.vehicle_type,Bus|nullable|string',
            'vehicles.*.pass_type'            => 'required|string|in:Private,Taxi',
            'vehicles.*.puc_expiry_date'      => 'nullable|date|after:today',
            'vehicles.*.insurance_expiry_date' => 'nullable|date|after:today',
            'vehicles.*.challan_count'        => 'nullable|integer|min:0',
            'vehicles.*.challan_amount'       => 'nullable|numeric|min:0',
            'vehicles.*.rc_book'              => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',

            'vehicles.*.driver_name'     => 'required|string|min:2|max:255',
            'vehicles.*.driver_mobile'   => ['required', 'string', 'regex:/^[6-9]\d{9}$/', 'unique:drivers,mobile_number'],
            'vehicles.*.driver_age'      => 'nullable|integer|min:18|max:70',
            'vehicles.*.driver_dl_number'  => 'nullable|string|max:20',
            'vehicles.*.driver_dl_expiry'  => 'nullable|date|after:today',
            'vehicles.*.driver_dl_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'vehicles.*.is_police_verified' => 'boolean',
            'vehicles.*.police_verification_document' => 'required_if:vehicles.*.is_police_verified,1|nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'owner_mobile.required'  => 'Owner mobile number is required.',
            'owner_mobile.regex'     => 'Owner mobile must be a valid 10-digit Indian number (starting with 6–9).',
            'owner_mobile.unique'    => 'This owner mobile number is already registered.',
            'owner_aadhaar_number.digits'  => 'Aadhaar number must be exactly 12 digits.',
            'owner_aadhaar_number.unique'  => 'This Aadhaar number is already registered.',
            'owner_pancard_number.unique'  => 'This PAN card number is already registered.',
            'vehicles.*.vehicle_number.required' => 'Vehicle number is required.',
            'vehicles.*.vehicle_number.unique'   => 'Vehicle # :input is already registered.',
            'vehicles.*.make_model.required'     => 'Make & Model is required.',
            'vehicles.*.pass_type.in'            => 'Pass type must be Private or Taxi.',
            'vehicles.*.puc_expiry_date.after'   => 'PUC expiry date must be a future date.',
            'vehicles.*.insurance_expiry_date.after' => 'Insurance expiry date must be a future date.',
            'vehicles.*.driver_name.required'    => 'Driver name is required.',
            'vehicles.*.driver_name.min'         => 'Driver name must be at least 2 characters.',
            'vehicles.*.driver_mobile.required'  => 'Driver mobile number is required.',
            'vehicles.*.driver_mobile.regex'     => 'Driver mobile must be a valid 10-digit Indian number (starting with 6–9).',
            'vehicles.*.driver_mobile.unique'    => 'Driver mobile :input is already registered with another driver.',
            'vehicles.*.driver_age.min'          => 'Driver must be at least 18 years old.',
            'vehicles.*.driver_age.max'          => 'Driver age cannot exceed 70 years.',
            'vehicles.*.driver_dl_expiry.after'  => 'Driving licence expiry must be a future date.',
            'vehicles.*.police_verification_document.required_if' => 'Please upload the Police Verification document for verified drivers.',
        ]);

        try {
            DB::beginTransaction();

            // 1. Create or Find Owner
            $owner = Owner::updateOrCreate(
                ['mobile' => $request->owner_mobile],
                [
                    'name' => $request->owner_name,
                    'aadhaar_number' => $request->owner_aadhaar_number,
                    'pancard_number' => $request->owner_pancard_number,
                    'address' => $request->owner_address,
                ]
            );

            foreach ($request->vehicles as $vehicleData) {
                // 2. Create Driver
                $driver = Driver::updateOrCreate(
                    ['mobile_number' => $vehicleData['driver_mobile']],
                    [
                        'name' => $vehicleData['driver_name'],
                        'age' => $vehicleData['driver_age'],
                        'driving_licence_number' => $vehicleData['driver_dl_number'],
                        'dl_expiry' => $vehicleData['driver_dl_expiry'],
                        'address' => $vehicleData['driver_address'] ?? null,
                        'is_police_verified' => $vehicleData['is_police_verified'] ?? false,
                        'status' => 'active',
                    ]
                );

                if (isset($vehicleData['driver_dl_document'])) {
                    $driver->update([
                        'driving_licence_document' => $vehicleData['driver_dl_document']->store('documents/dl', 'public')
                    ]);
                }

                if (isset($vehicleData['police_verification_document'])) {
                    $driver->update([
                        'police_verification_document' => $vehicleData['police_verification_document']->store('documents/police_verification', 'public')
                    ]);
                }

                // 3. Create Vehicle
                $vehicle = Vehicle::create([
                    'owner_id' => $owner->id,
                    'driver_id' => $driver->id,
                    'vehicle_number' => $vehicleData['vehicle_number'],
                    'vehicle_type' => $vehicleData['vehicle_type'],
                    'vehicle_type_custom' => $vehicleData['vehicle_type_custom'] ?? null,
                    'make_model' => $vehicleData['make_model'],
                    'fuel_type' => $vehicleData['fuel_type'],
                    'transmission_type' => $vehicleData['transmission_type'],
                    'color' => $vehicleData['color'] ?? null,
                    'pass_type' => $vehicleData['pass_type'],
                    'puc_expiry_date' => $vehicleData['puc_expiry_date'] ?? null,
                    'insurance_expiry_date' => $vehicleData['insurance_expiry_date'] ?? null,
                    'challan_count' => $vehicleData['challan_count'] ?? 0,
                    'challan_amount' => $vehicleData['challan_amount'] ?? 0,
                    'insurance_details' => $vehicleData['insurance_details'] ?? null,
                    'status' => 'active',
                    
                    // Legacy support
                    'is_driver_owner' => ($owner->mobile === $driver->mobile_number),
                    'owner_name' => $owner->name,
                    'owner_mobile' => $owner->mobile,
                ]);

                if (isset($vehicleData['rc_book'])) {
                    $vehicle->update([
                        'rc_book_path' => $vehicleData['rc_book']->store('documents/rc', 'public')
                    ]);
                }

                $this->auditLogger->log('Create Vehicle', 'vehicles', $vehicle->id, "Created vehicle {$vehicle->vehicle_number} for owner {$owner->name}");

                // Notify Driver
                if ($driver->fcm_token) {
                    $this->notificationService->sendNotification(
                        $driver->fcm_token,
                        "🚗 Vehicle Assigned",
                        "You have been assigned to vehicle {$vehicle->vehicle_number} ({$vehicle->make_model})",
                        ['link' => route('driver.dashboard')]
                    );
                }
            }

            DB::commit();

            return redirect()->route('admin.vehicles.index')
                ->with('success', count($request->vehicles) . ' Vehicle(s) and Driver(s) registered successfully.');

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
            'vehicle_number'      => 'required|string|max:20|unique:vehicles,vehicle_number,' . $vehicle->id,
            'make_model'          => 'required|string|max:255',
            'fuel_type'           => 'required|string',
            'transmission_type'   => 'required|string',
            'vehicle_type'        => 'required|string',
            'pass_type'           => 'required|string|in:Private,Taxi',
            'puc_expiry_date'     => 'nullable|date',
            'insurance_expiry_date' => 'nullable|date',
            'status'              => 'required|in:active,inactive,maintenance',
            'rc_book'             => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'driver_name'         => 'required|string|min:2|max:255',
            'driver_mobile'       => ['required', 'string', 'regex:/^[6-9]\d{9}$/', 'unique:drivers,mobile_number,' . $vehicle->driver_id],
            'driver_age'          => 'nullable|integer|min:18|max:70',
            'driver_dl_number'    => 'nullable|string|max:20',
            'driver_dl_document'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'is_police_verified'  => 'boolean',
            'police_verification_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'vehicle_number.unique'  => 'This vehicle number is already registered to another vehicle.',
            'vehicle_number.required' => 'Vehicle number is required.',
            'make_model.required'    => 'Make & Model is required.',
            'pass_type.in'           => 'Pass type must be Private or Taxi.',
            'status.in'              => 'Status must be active, inactive, or maintenance.',
            'driver_name.required'   => 'Driver name is required.',
            'driver_name.min'        => 'Driver name must be at least 2 characters.',
            'driver_mobile.required' => 'Driver mobile number is required.',
            'driver_mobile.regex'    => 'Driver mobile must be a valid 10-digit Indian number (starting with 6–9).',
            'driver_mobile.unique'   => 'This mobile number is already assigned to another driver.',
            'driver_age.min'         => 'Driver must be at least 18 years old.',
            'driver_age.max'         => 'Driver age cannot exceed 70 years.',
        ]);

        try {
            DB::beginTransaction();

            // 1. Update Driver
            $driver = $vehicle->driver;
            $driver->update([
                'name' => $request->driver_name,
                'mobile_number' => $request->driver_mobile,
                'age' => $request->driver_age,
                'driving_licence_number' => $request->driver_dl_number,
                'address' => $request->driver_address,
                'is_police_verified' => $request->boolean('is_police_verified'),
            ]);

            if ($request->hasFile('driver_dl_document')) {
                if ($driver->driving_licence_document) {
                    Storage::disk('public')->delete($driver->driving_licence_document);
                }
                $driver->update([
                    'driving_licence_document' => $request->file('driver_dl_document')->store('documents/dl', 'public')
                ]);
            }

            if ($request->hasFile('police_verification_document')) {
                if ($driver->police_verification_document) {
                    Storage::disk('public')->delete($driver->police_verification_document);
                }
                $driver->update([
                    'police_verification_document' => $request->file('police_verification_document')->store('documents/police_verification', 'public')
                ]);
            }

            // 2. Update Vehicle
            $vehicle->update([
                'vehicle_number' => $request->vehicle_number,
                'make_model' => $request->make_model,
                'vehicle_type' => $request->vehicle_type,
                'fuel_type' => $request->fuel_type,
                'transmission_type' => $request->transmission_type,
                'color' => $request->color,
                'pass_type' => $request->pass_type,
                'puc_expiry_date' => $request->puc_expiry_date,
                'insurance_expiry_date' => $request->insurance_expiry_date,
                'status' => $request->status,
            ]);

            if ($request->hasFile('rc_book')) {
                if ($vehicle->rc_book_path) {
                    Storage::disk('public')->delete($vehicle->rc_book_path);
                }
                $vehicle->update([
                    'rc_book_path' => $request->file('rc_book')->store('documents/rc', 'public')
                ]);
            }

            $this->auditLogger->log('Update Vehicle', 'vehicles', $vehicle->id, "Updated vehicle {$vehicle->vehicle_number}");

            // Notify Driver (if assigned)
            $driver = $vehicle->driver;
            if ($driver && $driver->fcm_token) {
                $this->notificationService->sendNotification(
                    $driver->fcm_token,
                    "📋 Vehicle Profile Updated",
                    "Your assigned vehicle {$vehicle->vehicle_number} has been updated.",
                    ['link' => route('driver.dashboard')]
                );
            }

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
