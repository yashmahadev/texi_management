<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckVehicleAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'taxi:check-alerts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expiring vehicle documents and send alerts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $threshold = now()->addDays(7);
        
        $expiring = \App\Models\Vehicle::where('status', 'active')
            ->where(function($q) use ($threshold) {
                $q->whereBetween('puc_expiry_date', [now(), $threshold])
                  ->orWhereBetween('insurance_expiry_date', [now(), $threshold]);
            })->get();

        foreach ($expiring as $vehicle) {
            $this->info("Alert: Vehicle {$vehicle->vehicle_number} has documents expiring soon.");
            
            // Log to system alerts/notifications if implemented
            \Illuminate\Support\Facades\Log::warning("Vehicle Expiry Alert", [
                'vehicle' => $vehicle->vehicle_number,
                'puc' => $vehicle->puc_expiry_date?->toDateString(),
                'insurance' => $vehicle->insurance_expiry_date?->toDateString(),
            ]);
            
            // In a real app, dispatch SendFcmNotification to Admin here
        }

        return 0;
    }
}
