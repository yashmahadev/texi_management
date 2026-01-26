<?php

namespace App\Services;

use App\Models\DirectBooking;
use App\Models\BookingFare;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class BookingFareService
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Calculate fare based on actual KM and settings
     */
    public function calculateFare(DirectBooking $booking, float $actualKm)
    {
        $baseFare = $booking->base_fare ?? $this->getBaseFare();
        $perKmRate = $booking->per_km_rate ?? $this->getPerKmRate();

        $calculatedFare = $baseFare + ($actualKm * $perKmRate);

        try {
            DB::beginTransaction();

            // Create or update fare record
            $fare = BookingFare::updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'base_fare' => $baseFare,
                    'per_km_rate' => $perKmRate,
                    'total_km' => $actualKm,
                    'calculated_fare' => $calculatedFare,
                    'final_fare' => $calculatedFare, // Will be overridden if admin adjusts
                    'calculated_by' => Auth::guard('web')->id() ?? null,
                ]
            );

            // Update booking actual_km
            $booking->update(['actual_km' => $actualKm]);

            // Audit log
            $this->auditLogService->log(
                'FARE_CALCULATED',
                'DirectBooking',
                $booking->id,
                "Fare calculated: Base ₹{$baseFare} + {$actualKm}KM × ₹{$perKmRate} = ₹{$calculatedFare}"
            );

            DB::commit();
            return $fare;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Admin adjustment of fare
     */
    public function adjustFare(DirectBooking $booking, float $newFare, string $reason)
    {
        $fare = $booking->fare;

        if (!$fare) {
            throw new \Exception('Fare must be calculated before it can be adjusted');
        }

        if ($fare->is_locked) {
            throw new \Exception('Fare is locked and cannot be adjusted');
        }

        try {
            DB::beginTransaction();

            $fare->update([
                'admin_adjusted_fare' => $newFare,
                'final_fare' => $newFare,
                'adjustment_reason' => $reason,
                'adjusted_by' => Auth::guard('web')->id(),
            ]);

            // Audit log
            $this->auditLogService->log(
                'FARE_ADJUSTED',
                'DirectBooking',
                $booking->id,
                "Fare adjusted from ₹{$fare->calculated_fare} to ₹{$newFare}. Reason: {$reason}"
            );

            DB::commit();
            return $fare;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Lock fare after trip completion (prevents further adjustment)
     */
    public function lockFare(DirectBooking $booking)
    {
        $fare = $booking->fare;

        if (!$fare) {
            throw new \Exception('No fare record found for this booking');
        }

        if ($fare->is_locked) {
            return $fare; // Already locked
        }

        try {
            DB::beginTransaction();

            $fare->update(['is_locked' => true]);

            // Audit log
            $this->auditLogService->log(
                'FARE_LOCKED',
                'DirectBooking',
                $booking->id,
                "Fare locked at ₹{$fare->final_fare}"
            );

            DB::commit();
            return $fare;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get base fare from settings
     */
    public function getBaseFare()
    {
        $setting = Setting::where('key', 'booking_base_fare')->first();
        return $setting ? (float) $setting->value : 50.00; // Default 50
    }

    /**
     * Get per KM rate from settings
     */
    public function getPerKmRate()
    {
        $setting = Setting::where('key', 'booking_per_km_rate')->first();
        return $setting ? (float) $setting->value : 10.00; // Default 10
    }

    /**
     * Create initial fare record when booking is created (with estimated km)
     */
    public function createEstimatedFare(DirectBooking $booking)
    {
        if ($booking->estimated_km && !$booking->fare) {
            $baseFare = $booking->base_fare ?? $this->getBaseFare();
            $perKmRate = $booking->per_km_rate ?? $this->getPerKmRate();
            $estimatedFare = $baseFare + ($booking->estimated_km * $perKmRate);

            BookingFare::updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'base_fare' => $baseFare,
                    'per_km_rate' => $perKmRate,
                    'total_km' => $booking->estimated_km,
                    'calculated_fare' => $estimatedFare,
                    'final_fare' => $estimatedFare,
                    'is_locked' => false,
                ]
            );
        }
    }
}
