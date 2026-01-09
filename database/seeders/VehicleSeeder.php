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

        $vendorPool = [
            ['name' => 'A1 Travels', 'mobile' => '8181818181'],
            ['name' => 'Shiva Logistics', 'mobile' => '8282828282'],
            ['name' => 'Gujarat Cab Corp', 'mobile' => '8383838383'],
            ['name' => 'Skyline Motors', 'mobile' => '8484848484'],
        ];
        
        try {
            $driverIndex = 0;
            foreach ($types as $type) {
                // Create 3 vehicles for each type (Total 9)
                for ($i = 1; $i <= 3; $i++) {
                    $driverInfo = $driverPool[$driverIndex % count($driverPool)];
                    
                    // 1. Create/Update Driver
                    $driver = \App\Models\Driver::updateOrCreate(
                        ['mobile_number' => $driverInfo['mobile']],
                        [
                            'name' => $driverInfo['name'],
                            'driving_licence_number' => 'DL-' . rand(10000, 99999) . 'GJ',
                            'aadhaar_number' => '1234' . rand(1000, 9999) . rand(1000, 9999),
                            'state' => 'Gujarat',
                            'city' => 'Ahmedabad',
                            'pincode' => '3800' . rand(10, 99),
                            'status' => 'active',
                        ]
                    );

                    // 2. Ownership / Vendor Logic
                    $isDriverOwner = ($driverIndex % 2 == 0);
                    if ($isDriverOwner) {
                        $ownerName = $driver->name;
                        $ownerMobile = $driver->mobile_number;
                    } else {
                        $vendor = $vendorPool[$driverIndex % count($vendorPool)];
                        $ownerName = $vendor['name'];
                        $ownerMobile = $vendor['mobile'];
                    }

                    // 3. Status Variety
                    $statuses = ['active', 'active', 'active', 'maintenance', 'inactive'];
                    $status = $statuses[$driverIndex % count($statuses)];

                    // 4. Create/Update Vehicle
                    Vehicle::updateOrCreate(
                        ['vehicle_number' => "GJ01" . strtoupper(substr($type, 0, 2)) . (8000 + $driverIndex)],
                        [
                            'vehicle_type' => $type,
                            'puc_expiry_date' => now()->addMonths(rand(-3, 12))->format('Y-m-d'),
                            'driver_id' => $driver->id,
                            'is_driver_owner' => $isDriverOwner,
                            'owner_name' => $ownerName,
                            'owner_mobile' => $ownerMobile,
                            'owner_aadhaar_number' => !$isDriverOwner ? '5678' . rand(1000, 9999) . rand(1000, 9999) : $driver->aadhaar_number,
                            'status' => $status,
                        ]
                    );

                    $driverIndex++;
                }
            }
        } catch (\Exception $e) {
            $this->command->error("Seeding failed: " . $e->getMessage());
            throw $e;
        }
    }
}
