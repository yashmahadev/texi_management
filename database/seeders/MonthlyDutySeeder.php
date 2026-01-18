<?php

namespace Database\Seeders;

use App\Models\MonthlyDuty;
use App\Models\Vehicle;
use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MonthlyDutySeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('monthly_duties')->truncate();
        Schema::enableForeignKeyConstraints();

        $vehicles = Vehicle::all();
        $departments = Department::all();

        if ($vehicles->isEmpty() || $departments->isEmpty()) {
            $this->command->error("Vehicles or Departments missing. Run their seeders first.");
            return;
        }

        $officers = ['Sanjay Patel', 'Rakesh Jha', 'Nirmal Singh', 'Sunil Varma', 'Prakash Raj'];

        foreach ($vehicles->take(5) as $index => $vehicle) {
            $dept = $departments->random();
            
            MonthlyDuty::create([
                'group' => $dept->group,
                'department_id' => $dept->id,
                'department_name' => $dept->name,
                'officer_name' => $officers[$index % count($officers)],
                'vehicle_id' => $vehicle->id,
                'primary_driver_id' => $vehicle->driver_id,
                'start_date' => now()->startOfMonth()->format('Y-m-d'),
                'end_date' => now()->endOfMonth()->format('Y-m-d'),
                'expected_start_time' => '09:00:00',
                'expected_end_time' => '18:00:00',
                'state' => 'Gujarat',
                'city' => 'Gandhinagar',
                'pincode' => '382010',
                'created_by' => 1,
            ]);
        }
    }
}
