<?php

namespace Database\Seeders;

use App\Models\Driver;
use Illuminate\Database\Seeder;

class DriverSeeder extends Seeder
{
    public function run(): void
    {
        $names = ['Ramesh Kumar', 'Suresh Patel', 'Mahesh Sharma', 'Rajesh Gupta'];
        
        foreach ($names as $index => $name) {
            Driver::create([
                'name' => $name,
                'mobile_number' => '+91869006583' . $index,
                'status' => 'active',
            ]);
        }
    }
}
