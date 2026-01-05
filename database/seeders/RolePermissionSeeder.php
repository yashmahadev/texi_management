<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            // Dashboard
            'view_admin_dashboard' => 'View Admin Dashboard',
            
            // Vehicles
            'view_vehicles' => 'View Vehicles',
            'create_vehicles' => 'Add Vehicles',
            'edit_vehicles' => 'Edit Vehicles',
            'delete_vehicles' => 'Delete Vehicles',
            
            
            // Duties
            'view_monthly_duties' => 'View Monthly Duties',
            'create_monthly_duties' => 'Create Monthly Duties',
            'edit_monthly_duties' => 'Edit Monthly Duties',
            'delete_monthly_duties' => 'Delete Monthly Duties',
            'view_daily_logs' => 'View Daily Logs',
            'edit_daily_logs' => 'Edit Daily Logs',
            'verify_daily_logs' => 'Verify Daily Logs',
            'view_replacements' => 'View Replacements',
            'assign_replacements' => 'Assign Replacements',
            
            // Reports & Audit
            'view_reports' => 'View Reports',
            'download_reports' => 'Download Reports',
            'view_audit_logs' => 'View Audit Logs',
            
            // Access Control
            'manage_roles' => 'Manage Roles',
            'manage_permissions' => 'Manage Permissions',
        ];

        foreach ($permissions as $name => $displayName) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['display_name' => $displayName]
            )->update(['display_name' => $displayName]);
        }

        // Create Roles
        $ownerRole = Role::firstOrCreate(['name' => 'owner']);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $driverRole = Role::firstOrCreate(['name' => 'driver', 'guard_name' => 'driver']); // Custom guard for drivers

        // Assign Permissions
        $permissionNames = array_keys($permissions);
        $ownerRole->givePermissionTo($permissionNames); // Owner has all permissions
        $adminRole->givePermissionTo($permissionNames); // Admin has all permissions (for now)
        
        // Driver permissions - strictly for the driver guard
        // Note: Spatie Permission separates permissions by guard. 
        // We need to create 'driver' guard permissions if we want to use them with the driver guard.
        // However, user requirement says "Enforce permissions using middleware & policies".
        // If drivers use a different guard, we must ensure permissions exist for that guard.
        
        $driverPermissions = [
            'view_monthly_duty',
            'log_duty',
        ];
        
        foreach ($driverPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'driver']);
        }
        
        $driverRole->givePermissionTo($driverPermissions);
    }
}
