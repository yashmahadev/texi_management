<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        $types = config('taxi.vehicle_types');
        
        foreach ($types as $type) {
            // Create 2 vehicles for each type
            for ($i = 1; $i <= 2; $i++) {
                Vehicle::create([
                    'vehicle_number' => "GJ01" . strtoupper(substr($type, 0, 2)) . "900" . $i,
                    'vehicle_type' => $type,
                    'status' => 'active',
                ]);
            }
        }
    }
}
