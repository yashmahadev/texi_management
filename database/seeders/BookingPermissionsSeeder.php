<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class BookingPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define Phase-2 permissions
        $permissions = [
            // Customer permissions
            ['name' => 'view_customers', 'guard_name' => 'web', 'display_name' => 'View Customers'],
            ['name' => 'create_customers', 'guard_name' => 'web', 'display_name' => 'Create Customers'],
            ['name' => 'edit_customers', 'guard_name' => 'web', 'display_name' => 'Edit Customers'],
            ['name' => 'delete_customers', 'guard_name' => 'web', 'display_name' => 'Delete Customers'],
            
            // Direct Booking permissions
            ['name' => 'view_direct_bookings', 'guard_name' => 'web', 'display_name' => 'View Direct Bookings'],
            ['name' => 'create_direct_bookings', 'guard_name' => 'web', 'display_name' => 'Create Direct Bookings'],
            ['name' => 'edit_direct_bookings', 'guard_name' => 'web', 'display_name' => 'Edit Direct Bookings'],
            ['name' => 'assign_drivers_to_bookings', 'guard_name' => 'web', 'display_name' => 'Assign Drivers to Bookings'],
            ['name' => 'cancel_bookings', 'guard_name' => 'web', 'display_name' => 'Cancel Bookings'],
            ['name' => 'adjust_booking_fares', 'guard_name' => 'web', 'display_name' => 'Adjust Booking Fares'],
            
            // Booking Reports
            ['name' => 'view_booking_reports', 'guard_name' => 'web', 'display_name' => 'View Booking Reports'],
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => $permission['guard_name']],
                ['display_name' => $permission['display_name']]
            );
        }

        // Assign all Phase-2 permissions to Admin role (if exists)
        $adminRole = Role::where('name', 'Admin')->where('guard_name', 'web')->first();
        
        if ($adminRole) {
            $permissionNames = array_column($permissions, 'name');
            $adminRole->givePermissionTo($permissionNames);
            $this->command->info('Phase-2 permissions assigned to Admin role');
        } else {
            $this->command->warn('Admin role not found. Please assign permissions manually.');
        }

        // Add default settings for booking fares
        DB::table('settings')->updateOrInsert(
            ['key' => 'booking_base_fare'],
            [
                'value' => '50',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        DB::table('settings')->updateOrInsert(
            ['key' => 'booking_per_km_rate'],
            [
                'value' => '10',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->command->info('Phase-2 permissions and settings seeded successfully!');
    }
}
