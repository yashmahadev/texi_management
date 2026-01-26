<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\DirectBooking;
use App\Services\DirectBookingService;
use App\Services\BookingFareService;
use App\Services\BookingCancellationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DirectBookingDriverController extends Controller
{
    protected $bookingService;
    protected $fareService;
    protected $cancellationService;

    public function __construct(
        DirectBookingService $bookingService,
        BookingFareService $fareService,
        BookingCancellationService $cancellationService
    ) {
        $this->bookingService = $bookingService;
        $this->fareService = $fareService;
        $this->cancellationService = $cancellationService;
    }

    /**
     * Display a listing of assigned bookings
     */
    public function index()
    {
        $driver = Auth::guard('driver')->user();
        $bookings = $this->bookingService->getDriverBookings($driver);

        return view('driver.direct_bookings.index', compact('bookings'));
    }

    /**
     * Display the specified booking
     */
    public function show(DirectBooking $booking)
    {
        $this->authorize('view', $booking);
        $booking->load(['customer', 'activeAssignment.vehicle', 'fare', 'statusLogs']);

        return view('driver.direct_bookings.show', compact('booking'));
    }

    /**
     * Accept the booking
     */
    public function accept(DirectBooking $booking)
    {
        $this->authorize('accept', $booking);

        try {
            $this->bookingService->updateStatus($booking, 'ACCEPTED', [], 'Booking accepted by driver');
            return redirect()->route('driver.direct-bookings.show', $booking)
                ->with('success', 'Booking accepted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reject the booking
     */
    public function reject(Request $request, DirectBooking $booking)
    {
        $this->authorize('reject', $booking);
        $request->validate(['reason' => 'required|string|max:500']);

        try {
            $this->cancellationService->rejectBooking($booking, $request->reason);
            return redirect()->route('driver.dashboard')
                ->with('success', 'Booking rejected and returned for reassignment.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Start the trip
     */
    public function start(DirectBooking $booking)
    {
        $this->authorize('start', $booking);

        try {
            $this->bookingService->updateStatus($booking, 'STARTED', [], 'Trip started');
            return redirect()->route('driver.direct-bookings.show', $booking)
                ->with('success', 'Trip started! Have a safe journey.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * End the trip
     */
    public function end(Request $request, DirectBooking $booking)
    {
        $this->authorize('end', $booking);
        $request->validate([
            'actual_km' => 'required|numeric|min:1',
            'remarks' => 'nullable|string|max:500'
        ]);

        try {
            // 1. Calculate fare and save KM
            $this->fareService->calculateFare($booking, $request->actual_km);

            // 2. Update status to COMPLETED
            $this->bookingService->updateStatus($booking, 'COMPLETED', [
                'actual_km' => $request->actual_km,
                'remarks' => $request->remarks
            ], 'Trip completed by driver');

            // 3. Lock fare
            $this->fareService->lockFare($booking);

            return redirect()->route('driver.direct-bookings.show', $booking)
                ->with('success', 'Trip completed successfully! Final fare calculated.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
