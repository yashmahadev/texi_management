<?php

namespace App\Services;

use App\Models\DirectBooking;
use App\Models\Driver;
use Illuminate\Support\Facades\Log;

class DirectBookingNotificationService
{
    protected $whatsAppService;
    protected $notificationService;

    public function __construct(WhatsAppService $whatsAppService, NotificationService $notificationService)
    {
        $this->whatsAppService = $whatsAppService;
        $this->notificationService = $notificationService;
    }

    /**
     * Notify customer about new booking creation
     */
    public function notifyBookingCreated(DirectBooking $booking)
    {
        $message = "Your booking {$booking->booking_number} is CREATED.\n" .
                   "Pickup: {$booking->pickup_location}\n" .
                   "Drop: {$booking->drop_location}\n" .
                   "Time: {$booking->booking_datetime->format('d M, h:i A')}\n" .
                   "We will notify you once a driver is assigned.";

        return $this->whatsAppService->sendWithTemplate($booking->customer_mobile, 'booking_created', [
            "1" => $booking->customer_name,
            "2" => $booking->booking_number,
            "3" => $booking->booking_datetime->format('d M, h:i A')
        ]) ?: $this->sendFallbackWhatsApp($booking->customer_mobile, $message);
    }

    /**
     * Notify driver about new assignment
     */
    public function notifyDriverAssigned(DirectBooking $booking, Driver $driver)
    {
        if ($driver->fcm_token) {
            $this->notificationService->sendNotification(
                $driver->fcm_token,
                "New Trip Assigned!",
                "Booking #{$booking->booking_number} for {$booking->customer_name}. Check your dashboard.",
                ['booking_id' => $booking->id, 'type' => 'DIRECT_BOOKING_ASSIGNED'],
                'driver'
            );
        }

        $message = "New Trip Assigned!\n" .
                   "Booking: #{$booking->booking_number}\n" .
                   "Customer: {$booking->customer_name}\n" .
                   "Pickup: {$booking->pickup_location}\n" .
                   "Time: {$booking->booking_datetime->format('d M, h:i A')}\n" .
                   "Please login to accept the trip.";

        return $this->whatsAppService->sendWithTemplate($driver->mobile_number, 'duty_assigned', [
            "1" => $driver->name,
            "2" => $booking->activeAssignment->vehicle->vehicle_number ?? 'Assigned Vehicle',
            "3" => $booking->booking_datetime->format('d M, h:i A'),
            "4" => $booking->pickup_location
        ]) ?: $this->sendFallbackWhatsApp($driver->mobile_number, $message);
    }

    /**
     * Notify customer that driver has accepted
     */
    public function notifyDriverAccepted(DirectBooking $booking)
    {
        $driver = $booking->activeAssignment->driver;
        $vehicle = $booking->activeAssignment->vehicle;

        $message = "Driver assigned for your booking {$booking->booking_number}!\n" .
                   "Driver: {$driver->name}\n" .
                   "Mobile: {$driver->mobile}\n" .
                   "Vehicle: {$vehicle->vehicle_number} ({$vehicle->vehicle_type})";

        return $this->whatsAppService->sendWithTemplate($booking->customer_mobile, 'driver_details', [
            "1" => $booking->booking_number,
            "2" => $driver->name,
            "3" => $driver->mobile_number,
            "4" => $vehicle->vehicle_number
        ]) ?: $this->sendFallbackWhatsApp($booking->customer_mobile, $message);
    }

    /**
     * Notify customer that trip has started
     */
    public function notifyTripStarted(DirectBooking $booking)
    {
        $message = "Your trip {$booking->booking_number} has STARTED. Have a safe journey!";

        return $this->whatsAppService->sendWithTemplate($booking->customer_mobile, 'trip_started', [
            "1" => $booking->booking_number
        ]) ?: $this->sendFallbackWhatsApp($booking->customer_mobile, $message);
    }

    /**
     * Notify customer about trip completion and fare
     */
    public function notifyTripCompleted(DirectBooking $booking)
    {
        if (!$booking->fare) return false;

        $message = "Your trip {$booking->booking_number} is COMPLETED.\n" .
                   "Total KM: {$booking->fare->total_km}\n" .
                   "Final Fare: ₹" . number_format($booking->fare->final_fare, 2) . "\n" .
                   "Thank you for choosing us!";

        return $this->whatsAppService->sendInvoice($booking->customer_mobile, [
            'customer_name' => $booking->customer_name,
            'amount' => number_format($booking->fare->final_fare, 2),
            'bill_no' => $booking->booking_number
        ]) ?: $this->sendFallbackWhatsApp($booking->customer_mobile, $message);
    }

    /**
     * Notify parties about cancellation
     */
    public function notifyBookingCancelled(DirectBooking $booking, string $reason)
    {
        $message = "IMPORTANT: Your booking {$booking->booking_number} has been CANCELLED.\nReason: {$reason}";

        // Notify Customer
        $this->whatsAppService->sendWithTemplate($booking->customer_mobile, 'duty_cancelled', [
            "1" => $booking->booking_number
        ]) ?: $this->sendFallbackWhatsApp($booking->customer_mobile, $message);

        // Notify Driver if assigned
        if ($booking->activeAssignment) {
            $driver = $booking->activeAssignment->driver;
            if ($driver->fcm_token) {
                $this->notificationService->sendNotification(
                    $driver->fcm_token,
                    "Trip Cancelled",
                    "Booking #{$booking->booking_number} for {$booking->customer_name} has been cancelled.",
                    ['booking_id' => $booking->id, 'type' => 'DIRECT_BOOKING_CANCELLED'],
                    'driver'
                );
            }
            $this->whatsAppService->sendWithTemplate($driver->mobile_number, 'duty_cancelled', [
                "1" => $booking->booking_number
            ]) ?: $this->sendFallbackWhatsApp($driver->mobile_number, $message);
        }
    }

    /**
     * Fallback to plain text WhatsApp if template fails or is not configured
     */
    protected function sendFallbackWhatsApp(string $phoneNumber, string $message)
    {
        // Internal method to trigger sendMessage directly if template naming strategy fails
        // We use a dummy type 'general_notification'
        $reflector = new \ReflectionClass($this->whatsAppService);
        $method = $reflector->getMethod('sendMessage');
        $method->setAccessible(true);
        return $method->invoke($this->whatsAppService, $phoneNumber, 'general_notification', $message);
    }
}
