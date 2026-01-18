<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
            OwnerSeeder::class,
            DepartmentSeeder::class,
            VehicleSeeder::class,
            MonthlyDutySeeder::class,
            DailyDutyLogSeeder::class,
            DutyReplacementSeeder::class,
            BillingLogSeeder::class,
            SettingSeeder::class,
        ]);
    }
}
