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
                'manage-system-settings',
                'manage-backups',
            ],
            'Employee Management' => [
                'view-employees',
                'create-employees',
                'edit-employees',
                'delete-employees',
            ],
            'Hospital Setup' => [
                'manage-departments',
                'manage-rooms',
            ],
            'Patient & Medical Records' => [
                'view-patients',
                'create-patients',
                'edit-patients',
                'delete-patients',
                'view-medical-records',
                'create-medical-records',
                'edit-medical-records',
                'enter-diagnoses',
                'record-vitals',
            ],
            'Clinical & Prescriptions' => [
                'view-doctors',
                'create-consultations',
                'write-prescriptions',
                'create-lab-orders',
                'view-lab-results',
                'manage-lab-tests',
            ],
            'Appointments & Rooms' => [
                'view-appointments',
                'create-appointments',
                'manage-inpatient-rooms',
            ],
            'Pharmacy Management' => [
                'view-medicines',
                'create-medicines',
                'edit-medicines',
                'delete-medicines',
                'restock-medicines',
                'dispense-medicines',
                'view-stock-alerts',
            ],
            'Billing & Payments' => [
                'view-invoices',
                'create-invoices',
                'process-payments',
                'cancel-invoices',
                'print-receipts',
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
            'admin' => [
                'manage-users',
                'view-users',
                'create-users',
                'edit-users',
                'delete-users',
                'manage-roles',
                'reset-2fa',
                'manage-system-settings',
                'manage-backups',
                'view-employees',
                'create-employees',
                'edit-employees',
                'delete-employees',
                'manage-departments',
                'manage-rooms',
                'view-patients',
                'view-doctors',
                'view-appointments',
                'view-lab-results',
                'view-medicines',
                'view-invoices',
            ],
            'doctor' => [
                'view-patients',
                'create-patients',
                'edit-patients',
                'view-medical-records',
                'create-medical-records',
                'edit-medical-records',
                'enter-diagnoses',
                'record-vitals',
                'view-doctors',
                'create-consultations',
                'write-prescriptions',
                'create-lab-orders',
                'view-lab-results',
                'view-appointments',
                'create-appointments',
                'manage-inpatient-rooms',
            ],
            'nurse' => [
                'view-patients',
                'create-patients',
                'edit-patients',
                'view-medical-records',
                'record-vitals',
                'view-doctors',
                'view-appointments',
                'create-appointments',
                'view-lab-results',
                'manage-inpatient-rooms',
            ],
            'pharmacist' => [
                'view-medicines',
                'create-medicines',
                'edit-medicines',
                'delete-medicines',
                'restock-medicines',
                'dispense-medicines',
                'view-stock-alerts',
            ],
            'cashier' => [
                'view-invoices',
                'create-invoices',
                'process-payments',
                'cancel-invoices',
                'print-receipts',
            ],
        ];

        // Create or update each role and sync permissions
        foreach ($rolesWithPermissions as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($permissions);
        }
    }
}