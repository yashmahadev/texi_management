<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        $types = config('taxi.vehicle_types');
        $driverNames = [
            'Ramesh Kumar', 'Suresh Patel', 'Mahesh Sharma', 'Rajesh Gupta', 
            'Amit Shah', 'Vijay Mallya', 'Narendra Modi', 'Rahul Gandhi'
        ];
        
        $driverIndex = 0;
        foreach ($types as $type) {
            // Create 2 vehicles for each type
            for ($i = 1; $i <= 2; $i++) {
                $driverName = $driverNames[$driverIndex % count($driverNames)] . " ($type $i)";
                
                // 1. Create Driver
                $driver = \App\Models\Driver::create([
                    'name' => $driverName,
                    'mobile_number' => '+9199' . str_pad($driverIndex, 8, '0', STR_PAD_LEFT),
                    'status' => 'active',
                ]);

                // 2. Decide Ownership
                $isDriverOwner = ($driverIndex % 2 == 0); // Toggle ownership for variety
                
                $ownerName = $isDriverOwner ? $driver->name : "Owner for " . $driver->name;
                $ownerMobile = $isDriverOwner ? $driver->mobile_number : '+9188' . str_pad($driverIndex, 8, '0', STR_PAD_LEFT);

                // 3. Create Vehicle
                Vehicle::create([
                    'vehicle_number' => "GJ01" . strtoupper(substr($type, 0, 2)) . "900" . $i . $driverIndex,
                    'vehicle_type' => $type,
                    'driver_id' => $driver->id,
                    'is_driver_owner' => $isDriverOwner,
                    'owner_name' => $ownerName,
                    'owner_mobile' => $ownerMobile,
                    'status' => 'active',
                ]);

                $driverIndex++;
            }
        }
    }
}
