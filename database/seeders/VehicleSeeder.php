<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('vehicles')->truncate();
        DB::table('drivers')->truncate();
        Schema::enableForeignKeyConstraints();

        $types = config('taxi.vehicle_types');
        $owners = \App\Models\Owner::all();
        
        if ($owners->isEmpty()) {
            $this->command->error("No owners found. Please run OwnerSeeder first.");
            return;
        }

        $driverPool = [
            ['name' => 'Rajesh Sharma', 'mobile' => '9876543210'],
            ['name' => 'Amit Verma', 'mobile' => '9123456780'],
            ['name' => 'Suresh Gupta', 'mobile' => '9898989898'],
            ['name' => 'Vijay Singh', 'mobile' => '9000000001'],
            ['name' => 'Mahesh Patel', 'mobile' => '9111111112'],
            ['name' => 'Karan Johar', 'mobile' => '9222222223'],
            ['name' => 'Salman Khan', 'mobile' => '9333333334'],
            ['name' => 'Shah Rukh', 'mobile' => '9444444445'],
            ['name' => 'Akshay Kumar', 'mobile' => '9555555556'],
            ['name' => 'Deepak Punia', 'mobile' => '9666666667'],
        ];

        $makes = ['Maruti Suzuki', 'Hyundai', 'Tata Motors', 'Toyota'];
        $models = ['Swift Dzire', 'Aura', 'Tigor', 'Innova Crysta'];
        $transmission = ['Manual', 'Automatic'];
        $fuels = ['CNG', 'Diesel', 'Petrol', 'Electric'];
        
        try {
            $index = 0;
            foreach ($types as $type) {
                // Create 3 vehicles for each type
                for ($i = 1; $i <= 3; $i++) {
                    $driverInfo = $driverPool[$index % count($driverPool)];
                    $owner = $owners->random();

                    // 1. Create/Update Driver
                    $driver = \App\Models\Driver::updateOrCreate(
                        ['mobile_number' => $driverInfo['mobile']],
                        [
                            'name' => $driverInfo['name'],
                            'age' => rand(25, 50),
                            'driving_licence_number' => 'DL-' . rand(10000, 99999) . 'GJ',
                            'address' => 'Sample Address ' . ($index + 1),
                            'is_police_verified' => (rand(1, 10) > 3),
                            'status' => 'active',
                        ]
                    );

                    // 2. Create/Update Vehicle
                    Vehicle::updateOrCreate(
                        ['vehicle_number' => "GJ01" . strtoupper(substr($type, 0, 2)) . (8000 + $index)],
                        [
                            'owner_id' => $owner->id,
                            'driver_id' => $driver->id,
                            'vehicle_type' => $type,
                            'make_model' => $makes[rand(0, 3)] . ' ' . $models[rand(0, 3)],
                            'fuel_type' => $fuels[rand(0, 3)],
                            'transmission_type' => $transmission[rand(0, 1)],
                            'color' => 'White',
                            'pass_type' => rand(0, 1) ? 'Private' : 'Taxi',
                            'puc_expiry_date' => now()->addMonths(rand(-3, 12))->format('Y-m-d'),
                            'status' => 'active',
                        ]
                    );

                    $index++;
                }
            }
        } catch (\Exception $e) {
            $this->command->error("Seeding failed: " . $e->getMessage());
            throw $e;
        }
    }
}
