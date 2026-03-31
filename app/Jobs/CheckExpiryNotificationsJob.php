<?php

namespace App\Jobs;

use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckExpiryNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Days before expiry to alert
    const ALERT_DAYS = [30, 7, 1];

    public function handle(NotificationService $notificationService, WhatsAppService $whatsapp): void
    {
        Log::info('CheckExpiryNotificationsJob: started');

        $today = Carbon::today();

        // Collect all admin FCM tokens for admin-side alerts
        $adminTokens = User::whereNotNull('fcm_token')->pluck('fcm_token')->toArray();

        foreach (self::ALERT_DAYS as $days) {
            $targetDate = $today->copy()->addDays($days)->toDateString();

            // --- Driver Licence Expiry ---
            Driver::where('status', 'active')
                ->whereDate('dl_expiry', $targetDate)
                ->each(function (Driver $driver) use ($days, $notificationService, $whatsapp, $adminTokens) {
                    $cacheKey = "expiry_dl_{$driver->id}_{$days}d";
                    if (cache()->has($cacheKey)) return;

                    $title = "⚠️ Driving Licence Expiring in {$days} Day(s)";
                    $body  = "Driver {$driver->name}'s DL expires on {$driver->dl_expiry->format('d M Y')}. Please renew it.";

                    // Notify driver
                    if ($driver->fcm_token) {
                        $notificationService->sendNotification($driver->fcm_token, $title, $body, [], 'driver');
                    }
                    $whatsapp->sendDelayAlert($driver->mobile_number, [
                        'department' => 'Licence Renewal',
                        'vehicle'    => 'DL: ' . $driver->driving_licence_number,
                        'time'       => $driver->dl_expiry->format('d M Y'),
                    ]);

                    // Notify all admins
                    foreach ($adminTokens as $token) {
                        $notificationService->sendNotification($token, $title, $body, [], 'web');
                    }

                    cache()->put($cacheKey, true, now()->addDay());
                    Log::info("DL expiry alert sent: Driver #{$driver->id}, {$days} days");
                });

            // --- Vehicle PUC Expiry ---
            Vehicle::where('status', 'active')
                ->whereDate('puc_expiry_date', $targetDate)
                ->with('driver')
                ->each(function (Vehicle $vehicle) use ($days, $notificationService, $whatsapp, $adminTokens) {
                    $cacheKey = "expiry_puc_{$vehicle->id}_{$days}d";
                    if (cache()->has($cacheKey)) return;

                    $title = "⚠️ PUC Certificate Expiring in {$days} Day(s)";
                    $body  = "Vehicle {$vehicle->vehicle_number}'s PUC expires on {$vehicle->puc_expiry_date->format('d M Y')}. Please renew it.";

                    if ($vehicle->driver && $vehicle->driver->fcm_token) {
                        $notificationService->sendNotification($vehicle->driver->fcm_token, $title, $body, [], 'driver');
                    }
                    if ($vehicle->driver) {
                        $whatsapp->sendDelayAlert($vehicle->driver->mobile_number, [
                            'department' => 'PUC Renewal',
                            'vehicle'    => $vehicle->vehicle_number,
                            'time'       => $vehicle->puc_expiry_date->format('d M Y'),
                        ]);
                    }

                    foreach ($adminTokens as $token) {
                        $notificationService->sendNotification($token, $title, $body, [], 'web');
                    }

                    cache()->put($cacheKey, true, now()->addDay());
                    Log::info("PUC expiry alert sent: Vehicle #{$vehicle->id}, {$days} days");
                });

            // --- Vehicle Insurance Expiry ---
            Vehicle::where('status', 'active')
                ->whereDate('insurance_expiry_date', $targetDate)
                ->with('driver')
                ->each(function (Vehicle $vehicle) use ($days, $notificationService, $whatsapp, $adminTokens) {
                    $cacheKey = "expiry_insurance_{$vehicle->id}_{$days}d";
                    if (cache()->has($cacheKey)) return;

                    $title = "⚠️ Insurance Expiring in {$days} Day(s)";
                    $body  = "Vehicle {$vehicle->vehicle_number}'s insurance expires on {$vehicle->insurance_expiry_date->format('d M Y')}. Please renew it.";

                    if ($vehicle->driver && $vehicle->driver->fcm_token) {
                        $notificationService->sendNotification($vehicle->driver->fcm_token, $title, $body, [], 'driver');
                    }
                    if ($vehicle->driver) {
                        $whatsapp->sendDelayAlert($vehicle->driver->mobile_number, [
                            'department' => 'Insurance Renewal',
                            'vehicle'    => $vehicle->vehicle_number,
                            'time'       => $vehicle->insurance_expiry_date->format('d M Y'),
                        ]);
                    }

                    foreach ($adminTokens as $token) {
                        $notificationService->sendNotification($token, $title, $body, [], 'web');
                    }

                    cache()->put($cacheKey, true, now()->addDay());
                    Log::info("Insurance expiry alert sent: Vehicle #{$vehicle->id}, {$days} days");
                });
        }

        Log::info('CheckExpiryNotificationsJob: completed');
    }
}
