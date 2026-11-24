<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * DashboardController (per-role dashboards) and DashboardExportController (XLSX).
 */
class DashboardTest extends TestCase
{
    use DatabaseTransactions;

    private const XLSX = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    public function test_guest_is_redirected(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/dashboard/export')->assertRedirect('/login');
    }

    public function test_admin_dashboard_renders(): void
    {
        $this->actingAsRole('admin');

        $this->get('/dashboard')->assertOk()->assertViewIs('form.dashboard.dashboard');
    }

    public function test_admin_dashboard_with_month_filter(): void
    {
        $this->actingAsRole('admin');

        $this->get('/dashboard?month=2026-01')->assertOk()->assertViewHas('selectedMonth', '2026-01');
    }

    public function test_admin_dashboard_with_invalid_month_falls_back(): void
    {
        $this->actingAsRole('admin');

        $this->get('/dashboard?month=garbage')->assertOk()->assertViewHas('selectedMonth', now()->format('Y-m'));
    }

    /** @dataProvider roleViews */
    public function test_role_dashboard_renders(string $role, string $view): void
    {
        $this->actingAsRole($role);

        $this->get('/dashboard')->assertOk()->assertViewIs($view);
    }

    public function roleViews(): array
    {
        return [
            'doctor' => ['doctor', 'form.dashboard.doctor'],
            'nurse' => ['nurse', 'form.dashboard.nurse'],
            'pharmacist' => ['pharmacist', 'form.dashboard.pharmacy'],
            'cashier' => ['cashier', 'form.dashboard.cashier'],
            'lab_technician' => ['lab_technician', 'form.home.home'],
        ];
    }

    // --------------------------------------------------------------- export

    public function test_admin_can_export_month(): void
    {
        $this->actingAsRole('admin');

        $res = $this->get('/dashboard/export?month=2026-01');

        $res->assertOk()->assertHeader('Content-Type', self::XLSX);
        $this->assertStringContainsString('PrumSantepheap_2026-01.xlsx', $res->headers->get('Content-Disposition'));
        $this->assertStringContainsString('attachment', $res->headers->get('Content-Disposition'));
    }

    public function test_admin_can_export_year(): void
    {
        $this->actingAsRole('admin');

        $res = $this->get('/dashboard/export?month=2026-03&scope=year');

        $res->assertOk()->assertHeader('Content-Type', self::XLSX);
        $this->assertStringContainsString('PrumSantepheap_2026_12-months.xlsx', $res->headers->get('Content-Disposition'));
    }

    public function test_export_with_invalid_month_uses_current_month(): void
    {
        $this->actingAsRole('admin');

        $res = $this->get('/dashboard/export?month=not-a-month');

        $res->assertOk();
        $this->assertStringContainsString('PrumSantepheap_' . now()->format('Y-m') . '.xlsx', $res->headers->get('Content-Disposition'));
    }

    public function test_export_body_is_a_valid_xlsx_zip(): void
    {
        $this->actingAsRole('admin');

        $body = $this->get('/dashboard/export?month=2026-01')->streamedContent();
        $this->assertStringStartsWith("PK", $body);
    }

    public function test_non_admins_cannot_export(): void
    {
        foreach (['doctor', 'nurse', 'pharmacist', 'cashier', 'lab_technician'] as $role) {
            $this->actingAsRole($role);
            $this->get('/dashboard/export')->assertForbidden();
        }
    }

    public function test_dashboard_requires_2fa_session(): void
    {
        $user = \App\Models\User::factory()->create();
        $user->assignRole('admin');
        $user->google2fa_secret = (new \PragmaRX\Google2FA\Google2FA())->generateSecretKey();
        $user->save();

        $this->actingAs($user);
        $this->get('/dashboard')->assertRedirect(route('2fa.verify'));
        $this->get('/dashboard/export')->assertRedirect(route('2fa.verify'));
    }
}
