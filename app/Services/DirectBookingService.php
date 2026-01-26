<?php

namespace App\Services;

use App\Models\DirectBooking;
use App\Models\BookingStatusLog;
use App\Models\Driver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DirectBookingService
{
    protected $auditLogService;
    protected $assignmentService;

    public function __construct(AuditLogService $auditLogService, BookingAssignmentService $assignmentService)
    {
        $this->auditLogService = $auditLogService;
        $this->assignmentService = $assignmentService;
    }

    /**
     * Create a new booking
     */
    public function createBooking(array $data)
    {
        try {
            DB::beginTransaction();

            // Create the booking
            $booking = DirectBooking::create($data);

            // Log initial status
            $this->logStatusChange($booking, null, 'CREATED', 'Booking created');

            // Audit log
            $this->auditLogService->log(
                'BOOKING_CREATED',
                'DirectBooking',
                $booking->id,
                "Booking {$booking->booking_number} created"
            );

            DB::commit();
            return $booking;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update booking (only allowed in CREATED status)
     */
    public function updateBooking(DirectBooking $booking, array $data)
    {
        if ($booking->status !== 'CREATED') {
            throw new \Exception('Only bookings in CREATED status can be edited');
        }

        try {
            DB::beginTransaction();

            $booking->update($data);

            $this->auditLogService->log(
                'BOOKING_UPDATED',
                'DirectBooking',
                $booking->id,
                "Booking {$booking->booking_number} updated"
            );

            DB::commit();
            return $booking;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Assign driver and vehicle to booking
     */
    public function assignDriver(DirectBooking $booking, int $driverId, int $vehicleId)
    {
        // Assignment service will handle validation and creation
        $assignment = $this->assignmentService->assignDriverToBooking($booking, $driverId, $vehicleId);

        // Update booking status
        $this->updateStatus($booking, 'ASSIGNED', [
            'driver_id' => $driverId,
            'vehicle_id' => $vehicleId,
        ]);

        return $assignment;
    }

    /**
     * Update booking status with validation
     */
    public function updateStatus(DirectBooking $booking, string $newStatus, array $metadata = [], ?string $remarks = null)
    {
        $currentStatus = $booking->status;

        // Validate transition
        if (!$this->validateStatusTransition($currentStatus, $newStatus)) {
            throw new \Exception("Invalid status transition from {$currentStatus} to {$newStatus}");
        }

        try {
            DB::beginTransaction();

            // Update booking status
            $booking->update(['status' => $newStatus]);

            // Log status change
            $this->logStatusChange($booking, $currentStatus, $newStatus, $remarks, $metadata);

            // Audit log
            $this->auditLogService->log(
                'BOOKING_STATUS_CHANGED',
                'DirectBooking',
                $booking->id,
                "Status changed from {$currentStatus} to {$newStatus}"
            );

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validate if status transition is allowed
     */
    public function validateStatusTransition(?string $currentStatus, string $newStatus): bool
    {
        // If current status is null, it's a fresh booking (likely CREATED)
        if ($currentStatus === null) {
            return true;
        }

        // Cannot change from terminal statuses
        if (in_array($currentStatus, ['COMPLETED', 'CANCELLED'])) {
            return false;
        }

        // Define allowed transitions
        $allowedTransitions = [
            'CREATED' => ['ASSIGNED', 'CANCELLED'],
            'ASSIGNED' => ['ACCEPTED', 'CREATED', 'CANCELLED'], // Can go back to CREATED for reassignment
            'ACCEPTED' => ['STARTED', 'CANCELLED'],
            'STARTED' => ['COMPLETED'], // Cannot cancel after started
        ];

        return isset($allowedTransitions[$currentStatus]) && 
               in_array($newStatus, $allowedTransitions[$currentStatus]);
    }

    /**
     * Log status change to booking_status_logs
     */
    protected function logStatusChange(DirectBooking $booking, ?string $fromStatus, string $toStatus, ?string $remarks = null, array $metadata = [])
    {
        $changedBy = null;
        $changedByType = null;

        if (Auth::guard('web')->check()) {
            $changedBy = Auth::guard('web')->id();
            $changedByType = \App\Models\User::class;
        } elseif (Auth::guard('driver')->check()) {
            $changedBy = Auth::guard('driver')->id();
            $changedByType = \App\Models\Driver::class;
        }

        BookingStatusLog::create([
            'booking_id' => $booking->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'changed_by' => $changedBy,
            'changed_by_type' => $changedByType,
            'remarks' => $remarks,
            'metadata' => !empty($metadata) ? $metadata : null,
        ]);
    }

    /**
     * Get bookings list with filters for admin
     */
    public function getBookingsList($filters = [])
    {
        $query = DirectBooking::with(['customer', 'creator', 'activeAssignment.driver', 'activeAssignment.vehicle']);

        // Filter by status
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        // Filter by date range
        if (isset($filters['from_date'])) {
            $query->where(function($q) use ($filters) {
                $q->whereDate('booking_datetime', '>=', $filters['from_date'])
                  ->orWhereDate('booking_end_datetime', '>=', $filters['from_date']);
            });
        }
        if (isset($filters['to_date'])) {
            $query->where(function($q) use ($filters) {
                $q->whereDate('booking_datetime', '<=', $filters['to_date'])
                  ->orWhereDate('booking_end_datetime', '<=', $filters['to_date']);
            });
        }

        // Search by booking number, customer name or mobile
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('booking_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_mobile', 'like', "%{$search}%");
            });
        }

        // Filter by customer
        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        return $query->latest('booking_datetime')->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get driver's bookings
     */
    public function getDriverBookings(Driver $driver, $status = null)
    {
        $query = DirectBooking::whereHas('activeAssignment', function($q) use ($driver) {
            $q->where('driver_id', $driver->id);
        })->with(['customer', 'activeAssignment.vehicle', 'fare']);

        if ($status) {
            if (is_array($status)) {
                $query->whereIn('status', $status);
            } else {
                $query->where('status', $status);
            }
        }

        return $query->latest('booking_datetime')->get();
    }
}
