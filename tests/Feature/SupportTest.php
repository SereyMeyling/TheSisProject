<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * SupportController: support/contact page (team members' phone + email).
 */
class SupportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_is_redirected(): void
    {
        $this->get('/support')->assertRedirect('/login');
    }

    public function test_every_role_can_view_support_page(): void
    {
        foreach (['admin', 'doctor', 'nurse', 'pharmacist', 'cashier', 'lab_technician'] as $role) {
            $this->actingAsRole($role);

            $this->get('/support')
                ->assertOk()
                ->assertViewIs('form.support.supportpage')
                // Member phone numbers shown with copy buttons
                ->assertSee('data-copy="0719899290"', false)
                ->assertSee('data-copy="0968538510"', false);
        }
    }

    public function test_support_page_shows_configured_contact_email(): void
    {
        $this->actingAsRole('nurse');
        // $setting is shared with all views at boot (AppServiceProvider).
        $expected = optional(\App\Models\Setting\GeneralSettings::first())->email ?? 'contact@gmail.com';

        $this->get('/support')->assertOk()->assertSee($expected);
    }
}
