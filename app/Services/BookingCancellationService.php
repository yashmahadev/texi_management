<?php

namespace App\Services;

use App\Models\DirectBooking;
use App\Models\BookingCancellation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class BookingCancellationService
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Cancel a booking with reason
     */
    public function cancelBooking(DirectBooking $booking, string $reason)
    {
        // Validate cancellation is allowed
        $this->validateCancellation($booking);

        try {
            DB::beginTransaction();

            // Update booking status to CANCELLED
            $booking->update(['status' => 'CANCELLED']);

            // Log cancellation
            $this->logCancellation($booking, $reason);

            // Log status change
            $cancelledBy = null;
            $cancelledByType = null;

            if (Auth::guard('web')->check()) {
                $cancelledBy = Auth::guard('web')->id();
                $cancelledByType = \App\Models\User::class;
            } elseif (Auth::guard('driver')->check()) {
                $cancelledBy = Auth::guard('driver')->id();
                $cancelledByType = \App\Models\Driver::class;
            }

            \App\Models\BookingStatusLog::create([
                'booking_id' => $booking->id,
                'from_status' => $booking->getOriginal('status'),
                'to_status' => 'CANCELLED',
                'changed_by' => $cancelledBy,
                'changed_by_type' => $cancelledByType,
                'remarks' => "Cancelled. Reason: {$reason}",
            ]);

            // Audit log
            $this->auditLogService->log(
                'BOOKING_CANCELLED',
                'DirectBooking',
                $booking->id,
                "Booking {$booking->booking_number} cancelled. Reason: {$reason}"
            );

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validate if cancellation is allowed
     */
    public function validateCancellation(DirectBooking $booking)
    {
        // Cannot cancel if already completed
        if ($booking->status === 'COMPLETED') {
            throw new \Exception('Cannot cancel a completed booking');
        }

        // Cannot cancel if already cancelled
        if ($booking->status === 'CANCELLED') {
            throw new \Exception('Booking is already cancelled');
        }

        // Cannot cancel if trip has started (business rule - can be adjusted)
        if ($booking->status === 'STARTED') {
            throw new \Exception('Cannot cancel a trip that has already started. Please complete the trip instead.');
        }

        return true;
    }

    /**
     * Create immutable cancellation record
     */
    protected function logCancellation(DirectBooking $booking, string $reason)
    {
        $cancelledBy = null;
        $cancelledByType = null;

        if (Auth::guard('web')->check()) {
            $cancelledBy = Auth::guard('web')->id();
            $cancelledByType = \App\Models\User::class;
        } elseif (Auth::guard('driver')->check()) {
            $cancelledBy = Auth::guard('driver')->id();
            $cancelledByType = \App\Models\Driver::class;
        }

        BookingCancellation::create([
            'booking_id' => $booking->id,
            'cancelled_by' => $cancelledBy,
            'cancelled_by_type' => $cancelledByType,
            'cancellation_reason' => $reason,
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Get cancellation details for a booking
     */
    public function getCancellationDetails(DirectBooking $booking)
    {
        return $booking->cancellation()->with('canceller')->first();
    }

    /**
     * Driver reject booking (special case of cancellation that allows reassignment)
     */
    public function driverRejectBooking(DirectBooking $booking, string $reason)
    {
        if ($booking->status !== 'ASSIGNED') {
            throw new \Exception('Only assigned bookings can be rejected by drivers');
        }

        try {
            DB::beginTransaction();

            // Deactivate current assignment
            $booking->activeAssignment()->update(['is_active' => false]);

            // Return booking to CREATED status for reassignment
            $booking->update(['status' => 'CREATED']);

            // Log the rejection
            $driverId = Auth::guard('driver')->id();
            \App\Models\BookingStatusLog::create([
                'booking_id' => $booking->id,
                'from_status' => 'ASSIGNED',
                'to_status' => 'CREATED',
                'changed_by' => $driverId,
                'changed_by_type' => \App\Models\Driver::class,
                'remarks' => "Driver rejected. Reason: {$reason}",
            ]);

            // Audit log
            $this->auditLogService->log(
                'BOOKING_REJECTED_BY_DRIVER',
                'DirectBooking',
                $booking->id,
                "Booking {$booking->booking_number} rejected by driver. Reason: {$reason}"
            );

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
