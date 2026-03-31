<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DirectBooking;
use App\Services\DirectBookingService;
use App\Services\BookingAssignmentService;
use App\Services\BookingFareService;
use App\Services\BookingCancellationService;
use App\Services\CustomerService;
use App\Http\Requests\StoreDirectBookingRequest;
use App\Http\Requests\UpdateDirectBookingRequest;
use App\Http\Requests\AssignDriverRequest;
use App\Http\Requests\CancelBookingRequest;
use App\Http\Requests\AdjustFareRequest;
use Illuminate\Http\Request;

class DirectBookingController extends Controller
{
    protected $bookingService;
    protected $assignmentService;
    protected $fareService;
    protected $cancellationService;
    protected $customerService;

    public function __construct(
        DirectBookingService $bookingService,
        BookingAssignmentService $assignmentService,
        BookingFareService $fareService,
        BookingCancellationService $cancellationService,
        CustomerService $customerService
    ) {
        $this->bookingService = $bookingService;
        $this->assignmentService = $assignmentService;
        $this->fareService = $fareService;
        $this->cancellationService = $cancellationService;
        $this->customerService = $customerService;
    }

    /**
     * Display listing of bookings
     */
    public function index(Request $request)
    {
        $sortable = ['booking_datetime', 'status', 'customer_name', 'booking_number', 'created_at'];
        $sort = in_array($request->sort, $sortable) ? $request->sort : 'booking_datetime';
        $dir  = $request->dir === 'asc' ? 'asc' : 'desc';

        $filters = [
            'status'      => $request->get('status'),
            'from_date'   => $request->get('from_date'),
            'to_date'     => $request->get('to_date'),
            'search'      => $request->get('search'),
            'customer_id' => $request->get('customer_id'),
            'per_page'    => 15,
            'sort'        => $sort,
            'dir'         => $dir,
        ];

        if ($request->export === 'csv') {
            return $this->exportCsv($this->bookingService->getBookingsList(array_merge($filters, ['per_page' => 99999]))->items());
        }

        $bookings  = $this->bookingService->getBookingsList($filters);
        $customers = $this->customerService->getActiveCustomersForDropdown();

        return view('admin.direct_bookings.index', compact('bookings', 'customers', 'sort', 'dir'));
    }

    private function exportCsv($bookings)
    {
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="direct_bookings.csv"'];
        $callback = function () use ($bookings) {
            $f = fopen('php://output', 'w');
            fputcsv($f, ['Booking #', 'Customer', 'Mobile', 'Pickup', 'Drop', 'Date', 'End Date', 'Driver', 'Vehicle', 'Status', 'Est. KM', 'Actual KM']);
            foreach ($bookings as $b) {
                fputcsv($f, [
                    $b->booking_number, $b->customer_name, $b->customer_mobile,
                    $b->pickup_location, $b->drop_location,
                    $b->booking_datetime->toDateTimeString(),
                    $b->booking_end_datetime?->toDateTimeString(),
                    $b->activeAssignment->driver->name ?? '',
                    $b->activeAssignment->vehicle->vehicle_number ?? '',
                    $b->status, $b->estimated_km, $b->actual_km,
                ]);
            }
            fclose($f);
        };
        return response()->stream($callback, 200, $headers);
    }

    /**
     * Show create booking form
     */
    public function create()
    {
        $customers = $this->customerService->getActiveCustomersForDropdown();
        $defaults = [
            'base_fare' => \App\Models\Setting::get('booking_base_fare', 50.00),
            'per_km_rate' => \App\Models\Setting::get('booking_per_km_rate', 10.00),
        ];
        return view('admin.direct_bookings.create', compact('customers', 'defaults'));
    }

    /**
     * Store new booking
     */
    public function store(StoreDirectBookingRequest $request)
    {
        try {
            $data = $request->validated();
            $data['created_by'] = auth()->id();

            // Extract driver/vehicle IDs if provided
            $driverId = $request->input('driver_id');
            $vehicleId = $request->input('vehicle_id');

            // Remove these from booking data as they're not fillable fields
            unset($data['driver_id'], $data['vehicle_id']);

            $booking = $this->bookingService->createBooking($data);

            // If both driver and vehicle are provided, assign them
            if ($driverId && $vehicleId) {
                try {
                    $this->bookingService->assignDriver($booking, $driverId, $vehicleId);
                    $message = "Booking {$booking->booking_number} created and driver assigned successfully!";
                } catch (\Exception $e) {
                    // If assignment fails, booking is still created but without assignment
                    $message = "Booking {$booking->booking_number} created successfully, but driver assignment failed: " . $e->getMessage();
                }
            } else {
                $message = "Booking {$booking->booking_number} created successfully! You can assign a driver from the booking details page.";
            }

            return redirect()->route('admin.direct-bookings.show', $booking)
                ->with('success', $message);
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to create booking: ' . $e->getMessage());
        }
    }

    /**
     * Show booking details
     */
    public function show(DirectBooking $booking)
    {
        $booking->load([
            'customer',
            'creator',
            'assignments.driver',
            'assignments.vehicle',
            'assignments.assignedBy',
            'activeAssignment.driver',
            'activeAssignment.vehicle',
            'statusLogs.changer',
            'fare',
            'cancellation.canceller'
        ]);

        // Get available drivers and vehicles if booking can be assigned
        $availableDrivers = [];
        $availableVehicles = [];

        if (in_array($booking->status, ['CREATED', 'ASSIGNED'])) {
            $availableDrivers = $this->assignmentService->getAvailableDrivers($booking->booking_datetime, $booking->booking_end_datetime);
            $availableVehicles = $this->assignmentService->getAvailableVehicles($booking->booking_datetime, $booking->booking_end_datetime);
        }

        return view('admin.direct_bookings.show', compact('booking', 'availableDrivers', 'availableVehicles'));
    }

    /**
     * Show edit form
     */
    public function edit(DirectBooking $booking)
    {
        // Only allow editing CREATED bookings
        if ($booking->status !== 'CREATED') {
            return redirect()->route('admin.direct-bookings.show', $booking)
                ->with('error', 'Only bookings in CREATED status can be edited.');
        }

        $customers = $this->customerService->getActiveCustomersForDropdown();
        return view('admin.direct_bookings.edit', compact('booking', 'customers'));
    }

    /**
     * Update booking
     */
    public function update(UpdateDirectBookingRequest $request, DirectBooking $booking)
    {
        try {
            $booking = $this->bookingService->updateBooking($booking, $request->validated());

            return redirect()->route('admin.direct-bookings.show', $booking)
                ->with('success', 'Booking updated successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to update booking: ' . $e->getMessage());
        }
    }

    /**
     * Assign driver to booking
     */
    public function assignDriver(AssignDriverRequest $request, DirectBooking $booking)
    {
        try {
            $this->bookingService->assignDriver(
                $booking,
                $request->driver_id,
                $request->vehicle_id
            );

            return redirect()->route('admin.direct-bookings.show', $booking)
                ->with('success', 'Driver assigned successfully!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel booking
     */
    public function cancel(CancelBookingRequest $request, DirectBooking $booking)
    {
        try {
            $this->cancellationService->cancelBooking($booking, $request->cancellation_reason);

            return redirect()->route('admin.direct-bookings.show', $booking)
                ->with('success', 'Booking cancelled successfully!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Adjust fare
     */
    public function adjustFare(AdjustFareRequest $request, DirectBooking $booking)
    {
        try {
            $this->fareService->adjustFare(
                $booking,
                $request->adjusted_fare,
                $request->adjustment_reason
            );

            return redirect()->route('admin.direct-bookings.show', $booking)
                ->with('success', 'Fare adjusted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get available drivers and vehicles for a booking datetime (AJAX)
     */
    public function getAvailableResources(Request $request)
    {
        $request->validate([
            'booking_datetime' => 'required|date',
            'booking_end_datetime' => 'nullable|date|after_or_equal:booking_datetime',
        ]);

        try {
            $bookingDateTime = \Carbon\Carbon::parse($request->booking_datetime);
            $bookingEndDateTime = $request->booking_end_datetime 
                ? \Carbon\Carbon::parse($request->booking_end_datetime) 
                : null;
            
            // Get available drivers with their vehicle relationship loaded
            $availableDrivers = $this->assignmentService->getAvailableDrivers($bookingDateTime, $bookingEndDateTime);
            // Load the vehicle relationship
            $availableDrivers->load('vehicle');
            
            // Get available vehicles with their driver relationship loaded
            $availableVehicles = $this->assignmentService->getAvailableVehicles($bookingDateTime, $bookingEndDateTime);
            // Load the driver relationship
            $availableVehicles->load('driver');

            return response()->json([
                'drivers' => $availableDrivers->map(function($driver) {
                    return [
                        'id' => $driver->id,
                        'name' => $driver->name,
                        'mobile' => $driver->mobile_number,
                        'vehicle_id' => $driver->vehicle ? $driver->vehicle->id : null,
                        'vehicle_number' => $driver->vehicle ? $driver->vehicle->vehicle_number : null,
                    ];
                }),
                'vehicles' => $availableVehicles->map(function($vehicle) {
                    return [
                        'id' => $vehicle->id,
                        'vehicle_number' => $vehicle->vehicle_number,
                        'vehicle_type' => $vehicle->vehicle_type,
                        'driver_name' => $vehicle->driver ? $vehicle->driver->name : null,
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
