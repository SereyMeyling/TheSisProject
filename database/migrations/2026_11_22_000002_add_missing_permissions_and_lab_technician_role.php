<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates permissions the code relies on but that were never seeded, so that
 * existing databases get them on `php artisan migrate` (no reseed needed):
 *   - edit-invoices            (BillingController edit/update)
 *   - manage-billing-settings  (settings/billing route + menu)
 *   - lab_technician role      (allowed by LabController / lab routes)
 * Also removes `view-medical-records` from nurses: the medical-records pages
 * are doctor-only, so the menu item only led to a 403.
 */
class AddMissingPermissionsAndLabTechnicianRole extends Migration
{
    public function up()
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['edit-invoices', 'manage-billing-settings', 'view-lab-results', 'view-medical-records'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $grant = [
            'admin' => ['edit-invoices', 'manage-billing-settings', 'view-medical-records'],
            'cashier' => ['edit-invoices', 'manage-billing-settings'],
        ];
        foreach ($grant as $roleName => $perms) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->givePermissionTo($perms);
            }
        }

        $labTech = Role::firstOrCreate(['name' => 'lab_technician', 'guard_name' => 'web']);
        $labTech->givePermissionTo('view-lab-results');

        $nurse = Role::where('name', 'nurse')->where('guard_name', 'web')->first();
        if ($nurse && $nurse->hasPermissionTo('view-medical-records')) {
            $nurse->revokePermissionTo('view-medical-records');
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down()
    {
        // Intentionally non-destructive: removing permissions could lock users out.
    }
}
