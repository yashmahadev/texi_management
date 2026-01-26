<?php

namespace App\Services;

use App\Models\DirectBooking;
use App\Models\BookingAssignment;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\MonthlyDuty;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class BookingAssignmentService
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Assign driver and vehicle to booking with validation
     */
    public function assignDriverToBooking(DirectBooking $booking, int $driverId, int $vehicleId)
    {
        $driver = Driver::findOrFail($driverId);
        $vehicle = Vehicle::findOrFail($vehicleId);

        // Validate availability
        $this->validateDriverAvailability($driver, $booking->booking_datetime, $booking->booking_end_datetime);
        $this->validateVehicleAvailability($vehicle, $booking->booking_datetime, $booking->booking_end_datetime);

        try {
            DB::beginTransaction();

            // Deactivate previous assignments
            $this->deactivatePreviousAssignments($booking);

            // Create new assignment
            $assignment = BookingAssignment::create([
                'booking_id' => $booking->id,
                'driver_id' => $driverId,
                'vehicle_id' => $vehicleId,
                'assigned_at' => now(),
                'assigned_by' => Auth::guard('web')->id(),
                'is_active' => true,
            ]);

            // Audit log
            $this->auditLogService->log(
                'DRIVER_ASSIGNED',
                'DirectBooking',
                $booking->id,
                "Driver {$driver->name} and Vehicle {$vehicle->vehicle_number} assigned to booking {$booking->booking_number}"
            );

            DB::commit();
            return $assignment;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validate driver availability
     * Checks for:
     * 1. Monthly duty conflicts (Phase-1)
     * 2. Overlapping direct bookings (Phase-2)
     */
    public function validateDriverAvailability(Driver $driver, Carbon $bookingDateTime, ?Carbon $bookingEndDateTime = null)
    {
        $bookingDate = $bookingDateTime->toDateString();
        $endDate = $bookingEndDateTime ? $bookingEndDateTime->toDateString() : $bookingDate;

        // Check Phase-1: Monthly duties
        $hasMonthlyDuty = MonthlyDuty::where('primary_driver_id', $driver->id)
            ->where(function($q) use ($bookingDate, $endDate) {
                // Check if any part of the booking range overlaps with monthly duty
                $q->where(function($query) use ($bookingDate, $endDate) {
                    $query->whereBetween('start_date', [$bookingDate, $endDate])
                          ->orWhereBetween('end_date', [$bookingDate, $endDate])
                          ->orWhere(function($q2) use ($bookingDate, $endDate) {
                              $q2->where('start_date', '<=', $bookingDate)
                                 ->where('end_date', '>=', $endDate);
                          });
                });
            })
            ->exists();

        if ($hasMonthlyDuty) {
            throw new \Exception("Driver {$driver->name} has a monthly duty assignment during this period. Please choose another driver.");
        }

        // Check Phase-2: Overlapping direct bookings
        $this->checkDirectBookingConflicts($driver->id, null, $bookingDateTime, $bookingEndDateTime, 'Driver');

        return true;
    }

    /**
     * Validate vehicle availability
     * Check for overlapping direct bookings
     */
    public function validateVehicleAvailability(Vehicle $vehicle, Carbon $bookingDateTime, ?Carbon $bookingEndDateTime = null)
    {
        $this->checkDirectBookingConflicts(null, $vehicle->id, $bookingDateTime, $bookingEndDateTime, 'Vehicle');
        return true;
    }

    /**
     * Check for direct booking conflicts (multi-day support)
     */
    protected function checkDirectBookingConflicts(?int $driverId, ?int $vehicleId, Carbon $bookingDateTime, ?Carbon $bookingEndDateTime, string $resourceType)
    {
        $query = DirectBooking::whereIn('status', ['ASSIGNED', 'ACCEPTED', 'STARTED']);

        // Filter by driver or vehicle
        if ($driverId) {
            $query->whereHas('activeAssignment', function($q) use ($driverId) {
                $q->where('driver_id', $driverId);
            });
        } elseif ($vehicleId) {
            $query->whereHas('activeAssignment', function($q) use ($vehicleId) {
                $q->where('vehicle_id', $vehicleId);
            });
        }

        // Check for date range overlap
        if ($bookingEndDateTime) {
            // Multi-day booking: check if any part of the date range overlaps
            $query->where(function($q) use ($bookingDateTime, $bookingEndDateTime) {
                $q->where(function($subQ) use ($bookingDateTime, $bookingEndDateTime) {
                    // Case 1: Existing booking starts or ends within our date range
                    $subQ->whereBetween('booking_datetime', [$bookingDateTime, $bookingEndDateTime])
                         ->orWhereBetween('booking_end_datetime', [$bookingDateTime, $bookingEndDateTime])
                         // Case 2: Our booking is completely within an existing booking
                         ->orWhere(function($q2) use ($bookingDateTime, $bookingEndDateTime) {
                             $q2->where('booking_datetime', '<=', $bookingDateTime)
                                ->where(function($q3) use ($bookingEndDateTime) {
                                    $q3->where('booking_end_datetime', '>=', $bookingEndDateTime)
                                       ->orWhereNull('booking_end_datetime');
                                });
                         });
                });
            });
        } else {
            // Single-day booking: use 2-hour buffer window
            $bufferHours = 2;
            $startWindow = $bookingDateTime->copy()->subHours($bufferHours);
            $endWindow = $bookingDateTime->copy()->addHours($bufferHours);

            $query->where(function($q) use ($startWindow, $endWindow) {
                $q->whereBetween('booking_datetime', [$startWindow, $endWindow]);
            });
        }

        if ($query->exists()) {
            $resourceName = $driverId ? Driver::find($driverId)->name : Vehicle::find($vehicleId)->vehicle_number;
            throw new \Exception("{$resourceType} {$resourceName} has an overlapping booking during this period. Please choose another {$resourceType} or adjust the dates.");
        }
    }

    /**
     * Deactivate previous assignments for this booking
     */
    public function deactivatePreviousAssignments(DirectBooking $booking)
    {
        BookingAssignment::where('booking_id', $booking->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    /**
     * Get assignment history for a booking
     */
    public function getAssignmentHistory(DirectBooking $booking)
    {
        return $booking->assignments()
            ->with(['driver', 'vehicle', 'assignedBy'])
            ->orderBy('assigned_at', 'desc')
            ->get();
    }

    /**
     * Get available drivers for a booking datetime (with optional end date for multi-day bookings)
     */
    public function getAvailableDrivers(Carbon $bookingDateTime, ?Carbon $bookingEndDateTime = null)
    {
        $bookingDate = $bookingDateTime->toDateString();
        $endDate = $bookingEndDateTime ? $bookingEndDateTime->toDateString() : $bookingDate;

        // Get drivers with monthly duties during this date range
        $busyDriversFromDuties = MonthlyDuty::where(function($q) use ($bookingDate, $endDate) {
                $q->whereBetween('start_date', [$bookingDate, $endDate])
                  ->orWhereBetween('end_date', [$bookingDate, $endDate])
                  ->orWhere(function($q2) use ($bookingDate, $endDate) {
                      $q2->where('start_date', '<=', $bookingDate)
                         ->where('end_date', '>=', $endDate);
                  });
            })
            ->pluck('primary_driver_id')
            ->toArray();

        // Get drivers with overlapping bookings
        $busyDriversQuery = BookingAssignment::where('is_active', true)
            ->whereHas('booking', function($q) use ($bookingDateTime, $bookingEndDateTime) {
                $q->whereIn('status', ['ASSIGNED', 'ACCEPTED', 'STARTED']);
                
                if ($bookingEndDateTime) {
                    // Multi-day: check date range overlap
                    $q->where(function($subQ) use ($bookingDateTime, $bookingEndDateTime) {
                        $subQ->whereBetween('booking_datetime', [$bookingDateTime, $bookingEndDateTime])
                             ->orWhereBetween('booking_end_datetime', [$bookingDateTime, $bookingEndDateTime])
                             ->orWhere(function($q2) use ($bookingDateTime, $bookingEndDateTime) {
                                 $q2->where('booking_datetime', '<=', $bookingDateTime)
                                    ->where(function($q3) use ($bookingEndDateTime) {
                                        $q3->where('booking_end_datetime', '>=', $bookingEndDateTime)
                                           ->orWhereNull('booking_end_datetime');
                                    });
                             });
                    });
                } else {
                    // Single-day: use 2-hour buffer
                    $bufferHours = 2;
                    $startWindow = $bookingDateTime->copy()->subHours($bufferHours);
                    $endWindow = $bookingDateTime->copy()->addHours($bufferHours);
                    $q->whereBetween('booking_datetime', [$startWindow, $endWindow]);
                }
            });

        $busyDriversFromBookings = $busyDriversQuery->pluck('driver_id')->toArray();

        $busyDriverIds = array_unique(array_merge($busyDriversFromDuties, $busyDriversFromBookings));

        return Driver::where('status', 'active')
            ->whereNotIn('id', $busyDriverIds)
            ->get();
    }

    /**
     * Get available vehicles for a booking datetime (with optional end date for multi-day bookings)
     */
    public function getAvailableVehicles(Carbon $bookingDateTime, ?Carbon $bookingEndDateTime = null)
    {
        $busyVehiclesQuery = BookingAssignment::where('is_active', true)
            ->whereHas('booking', function($q) use ($bookingDateTime, $bookingEndDateTime) {
                $q->whereIn('status', ['ASSIGNED', 'ACCEPTED', 'STARTED']);
                
                if ($bookingEndDateTime) {
                    // Multi-day: check date range overlap
                    $q->where(function($subQ) use ($bookingDateTime, $bookingEndDateTime) {
                        $subQ->whereBetween('booking_datetime', [$bookingDateTime, $bookingEndDateTime])
                             ->orWhereBetween('booking_end_datetime', [$bookingDateTime, $bookingEndDateTime])
                             ->orWhere(function($q2) use ($bookingDateTime, $bookingEndDateTime) {
                                 $q2->where('booking_datetime', '<=', $bookingDateTime)
                                    ->where(function($q3) use ($bookingEndDateTime) {
                                        $q3->where('booking_end_datetime', '>=', $bookingEndDateTime)
                                           ->orWhereNull('booking_end_datetime');
                                    });
                             });
                    });
                } else {
                    // Single-day: use 2-hour buffer
                    $bufferHours = 2;
                    $startWindow = $bookingDateTime->copy()->subHours($bufferHours);
                    $endWindow = $bookingDateTime->copy()->addHours($bufferHours);
                    $q->whereBetween('booking_datetime', [$startWindow, $endWindow]);
                }
            });

        $busyVehicleIds = $busyVehiclesQuery->pluck('vehicle_id')->toArray();

        return Vehicle::where('status', 'active')
            ->whereNotIn('id', $busyVehicleIds)
            ->get();
    }
}
