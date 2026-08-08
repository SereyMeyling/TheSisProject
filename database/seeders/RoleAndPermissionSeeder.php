<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // 1. Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Define Permissions Grouped by Module
        $permissionsByModule = [
            'User & System Management' => [
                'manage-users',
                'view-users',
                'create-users',
                'edit-users',
                'delete-users',
                'manage-roles',
                'reset-2fa',
            ],
            'Patient Management' => [
                'view-patients',
                'create-patients',
                'edit-patients',
                'delete-patients',
            ],
            'Doctor Management' => [
                'view-doctors',
                'create-doctors',
                'edit-doctors',
                'delete-doctors',
            ],
            'Pharmacy Management' => [
                'view-medicines',
                'create-medicines',
                'edit-medicines',
                'delete-medicines',
                'dispense-medicines',
            ],
            'Billing & Payments' => [
                'view-invoices',
                'create-invoices',
                'process-payments',
                'cancel-invoices',

            ],
            'Lab & Appointments' => [
                'view-appointments',
                'create-appointments',
                'view-lab-results',
                'create-lab-orders',
            ],
        ];

        // Create all permissions using firstOrCreate
        foreach ($permissionsByModule as $module => $permissions) {
            foreach ($permissions as $permissionName) {
                Permission::firstOrCreate([
                    'name'       => $permissionName,
                    'guard_name' => 'web',
                ]);
            }
        }

        // 3. Define Roles & Permission Assignments
        $rolesWithPermissions = [
            'doctor' => [
                'view-patients',
                'edit-patients',
                'view-doctors',
                'edit-doctors',
                'view-medicines',
                'view-appointments',
                'create-appointments',
                'view-lab-results',
                'create-lab-orders',
            ],
            'nurse' => [
                'view-patients',
                'create-patients',
                'edit-patients',
                'view-doctors',
                'view-appointments',
                'create-appointments',
                'view-lab-results',
            ],
            'pharmacist' => [
                'view-patients',
                'view-medicines',
                'create-medicines',
                'edit-medicines',
                'delete-medicines',
                'dispense-medicines',
            ],
            'cashier' => [
                'view-patients',
                'view-invoices',
                'create-invoices',
                'process-payments', 
            ],
        ];

        // Create 'admin' role and assign ALL permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions(Permission::all());

        // Create other roles and sync their respective permissions
        foreach ($rolesWithPermissions as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($permissions);
        }
    }
}