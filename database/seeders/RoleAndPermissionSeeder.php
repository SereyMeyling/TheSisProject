<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Create Roles
        $adminRole      = Role::create(['name' => 'admin']);
        $doctorRole     = Role::create(['name' => 'doctor']);
        $cashierRole    = Role::create(['name' => 'cashier']);
        $nurseRole      = Role::create(['name' => 'nurse']);
        $pharmacistRole = Role::create(['name' => 'pharmacist']);

        // 2. Create Permissions
        $createDoctorPermission  = Permission::create(['name' => 'create doctors']);
        $createPatientPermission = Permission::create(['name' => 'create patients']);

        // 3. Assign Permissions to Roles
        $adminRole->givePermissionTo($createDoctorPermission);
        $adminRole->givePermissionTo($createPatientPermission);
    }
}