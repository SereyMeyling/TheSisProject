<?php

namespace Tests\Feature;

use App\Models\Setting\GeneralSettings;
use App\Models\Setting\InvoiceSetting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * GeneralSettingsController, SettingsController (qrcode + billing),
 * BackupController (access control / page render only — no backups run).
 */
class SettingsTest extends TestCase
{
    use DatabaseTransactions;

    private const NON_ADMIN_ROLES = ['doctor', 'nurse', 'pharmacist', 'cashier', 'lab_technician'];

    // ---------------------------------------------------- public branding

    public function test_logo_and_favicon_are_public_and_served_with_mime(): void
    {
        $png = base64_encode(UploadedFile::fake()->image('l.png', 4, 4)->get());
        $setting = GeneralSettings::first() ?? new GeneralSettings();
        $setting->fill([
            'logo_data' => $png, 'logo_mime' => 'image/png',
            'favicon_data' => $png, 'favicon_mime' => 'image/png',
        ])->save();

        // Guest (login page needs these)
        $this->get('/settings/general/logo')->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get('/settings/general/favicon')->assertOk()->assertHeader('Content-Type', 'image/png');
    }

    public function test_logo_404_when_not_set(): void
    {
        $setting = GeneralSettings::first();
        if ($setting) {
            $setting->forceFill(['logo_data' => null, 'favicon_data' => null])->save();
        }

        $this->get('/settings/general/logo')->assertNotFound();
        $this->get('/settings/general/favicon')->assertNotFound();
    }

    // ------------------------------------------------------ general page

    public function test_admin_can_view_and_update_general_settings(): void
    {
        $this->actingAsRole('admin');

        $this->get('/settings/general')->assertOk();

        $this->from('/settings/general')->post('/settings/general', [
            'system_name' => 'Test Hospital',
            'phone' => '012 345 678',
            'email' => 'info@hospital.test',
            'logo' => UploadedFile::fake()->image('logo.png', 10, 10),
        ])->assertRedirect('/settings/general')->assertSessionHas('success');

        $s = GeneralSettings::first();
        $this->assertSame('Test Hospital', $s->system_name);
        $this->assertSame('image/png', $s->logo_mime);

        $this->get('/settings/general/logo')->assertOk()->assertHeader('Content-Type', 'image/png');
    }

    public function test_general_settings_validation(): void
    {
        $this->actingAsRole('admin');

        $this->post('/settings/general', ['email' => 'not-an-email'])->assertSessionHasErrors('email');
        $this->post('/settings/general', [
            'logo' => UploadedFile::fake()->create('evil.html', 1, 'text/html'),
        ])->assertSessionHasErrors('logo');
        $this->post('/settings/general', [
            'favicon' => UploadedFile::fake()->create('evil.svg', 1, 'image/svg+xml'),
        ])->assertSessionHasErrors('favicon');
    }

    public function test_general_settings_ignore_non_whitelisted_fields(): void
    {
        $this->actingAsRole('admin');

        $this->post('/settings/general', [
            'system_name' => 'Safe',
            'logo_mime' => 'text/html',
            'logo_data' => base64_encode('<script>alert(1)</script>'),
        ])->assertSessionHasNoErrors();

        $s = GeneralSettings::first();
        $this->assertNotSame('text/html', $s->logo_mime);
        $this->assertNotSame(base64_encode('<script>alert(1)</script>'), $s->logo_data);
    }

    // ------------------------------------------------------------ qrcode

    public function test_admin_can_view_and_update_qrcode_settings(): void
    {
        $this->actingAsRole('admin');

        $this->get('/settings/qrcode')->assertOk();

        $this->postJson('/settings/qrcode', [
            'mode' => 'manual',
            'bank_name' => 'ABA',
            'account_name' => 'Test Account',
            'account_number' => '000111222',
            'manual_qr_image' => UploadedFile::fake()->image('qr.png', 20, 20),
        ])->assertOk()->assertJsonPath('image_saved', true);

        $this->postJson('/settings/qrcode', ['mode' => 'bogus'])
            ->assertStatus(422)->assertJsonStructure(['errors' => ['mode', 'bank_name', 'account_name', 'account_number']]);
    }

    // ----------------------------------------------------------- billing

    public function test_admin_and_cashier_can_use_billing_settings(): void
    {
        foreach (['admin', 'cashier'] as $role) {
            $this->actingAsRole($role);

            $this->get('/settings/billing')->assertOk();

            $this->postJson('/settings/billing', [
                'currency_symbol' => '$',
                'secondary_currency_symbol' => '៛',
                'exchange_rate' => 4100,
                'tax_percent' => 10,
                'invoice_footer' => "Footer by {$role}",
            ])->assertOk();

            $this->assertSame("Footer by {$role}", InvoiceSetting::find(1)->invoice_footer);
        }
    }

    public function test_billing_settings_validation(): void
    {
        $this->actingAsRole('admin');

        $this->postJson('/settings/billing', [
            'currency_symbol' => '$',
            'secondary_currency_symbol' => '៛',
            'exchange_rate' => 0,
            'tax_percent' => 150,
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['exchange_rate', 'tax_percent']]);
    }

    public function test_other_roles_cannot_use_billing_settings(): void
    {
        foreach (['doctor', 'nurse', 'pharmacist', 'lab_technician'] as $role) {
            $this->actingAsRole($role);

            $this->get('/settings/billing')->assertForbidden();
            $this->postJson('/settings/billing', ['currency_symbol' => 'X'])->assertForbidden();
        }
    }

    public function test_cashier_without_permission_is_forbidden_from_billing_settings(): void
    {
        // Role check passes but the permission:manage-billing-settings middleware must still apply.
        // (revocation is rolled back with the test transaction)
        $user = $this->actingAsRole('cashier');
        $user->roles->first()->revokePermissionTo('manage-billing-settings');
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->get('/settings/billing')->assertForbidden();
    }

    // -------------------------------------------- admin-only setting pages

    public function test_non_admins_cannot_access_admin_settings(): void
    {
        foreach (self::NON_ADMIN_ROLES as $role) {
            $this->actingAsRole($role);

            $this->get('/settings/general')->assertForbidden();
            $this->post('/settings/general', ['system_name' => 'Hacked'])->assertForbidden();
            $this->get('/settings/qrcode')->assertForbidden();
            $this->post('/settings/qrcode', ['mode' => 'manual'])->assertForbidden();
        }

        $this->assertNotSame('Hacked', optional(GeneralSettings::first())->system_name);
    }

    // ------------------------------------------------------------ backup

    public function test_admin_can_view_backup_page_and_list(): void
    {
        $this->actingAsRole('admin');

        $this->get('/settings/backup')->assertOk();
        $this->getJson('/settings/backup/list')->assertOk()->assertJsonStructure(['data']);
    }

    public function test_non_admins_cannot_access_backups(): void
    {
        foreach (self::NON_ADMIN_ROLES as $role) {
            $this->actingAsRole($role);

            $this->get('/settings/backup')->assertForbidden();
            $this->getJson('/settings/backup/list')->assertForbidden();
            $this->postJson('/settings/backup/create')->assertForbidden();
            $this->postJson('/settings/backup/restore')->assertForbidden();
            $this->get('/settings/backup/download/x.zip')->assertForbidden();
            $this->deleteJson('/settings/backup/x.zip')->assertForbidden();
        }
    }

    public function test_guest_cannot_access_backups(): void
    {
        $this->get('/settings/backup')->assertRedirect('/login');
        $this->post('/settings/backup/create')->assertRedirect('/login');
    }

    /** bug.md M21: downloading a full dump requires re-entering the password. */
    public function test_backup_download_requires_password_confirmation(): void
    {
        $this->actingAsRole('admin');

        $this->get('/settings/backup/download/anything.zip')->assertRedirect(route('password.confirm'));
    }

    public function test_backup_download_after_confirm_unknown_file_is_404(): void
    {
        $this->actingAsRole('admin');

        $this->withSession(['2fa_passed' => true, 'auth.password_confirmed_at' => time()])
            ->get('/settings/backup/download/' . 'nope-' . uniqid() . '.zip')
            ->assertNotFound();
    }

    public function test_delete_unknown_backup_is_404(): void
    {
        $this->actingAsRole('admin');

        $this->deleteJson('/settings/backup/nope-' . uniqid() . '.zip')->assertNotFound();
    }

    public function test_restore_without_file_is_rejected(): void
    {
        $this->actingAsRole('admin');
        $before = DB::table('users')->count();

        $this->postJson('/settings/backup/restore', [])
            ->assertStatus(422)->assertJsonValidationErrors(['backup_file', 'current_password']);
        $this->assertSame($before, DB::table('users')->count());
    }
}
