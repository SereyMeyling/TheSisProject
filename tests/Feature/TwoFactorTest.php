<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * TwoFactorController + EnsureTwoFactorIsVerified middleware.
 */
class TwoFactorTest extends TestCase
{
    use DatabaseTransactions;

    private Google2FA $g2fa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->g2fa = new Google2FA();
    }

    private function userWithoutSecret(): User
    {
        $user = User::factory()->create();
        $user->assignRole('nurse');

        return $user;
    }

    private function userWithSecret(?string $secret = null): User
    {
        $user = $this->userWithoutSecret();
        $user->google2fa_secret = $secret ?? $this->g2fa->generateSecretKey();
        $user->save();

        return $user->fresh();
    }

    private function lockedUser(): User
    {
        $user = $this->userWithoutSecret();
        DB::table('users')->where('id', $user->id)->update(['google2fa_secret' => 'garbage-not-decryptable']);

        return $user->fresh();
    }

    // ---------------------------------------------------------------- setup

    public function test_user_without_secret_is_sent_to_setup_from_protected_pages(): void
    {
        $this->actingAs($this->userWithoutSecret());

        $this->get('/dashboard')->assertRedirect(route('2fa.setup'));
        $this->get('/profile')->assertRedirect(route('2fa.setup'));
        $this->get('/notifications')->assertRedirect(route('2fa.setup'));
    }

    public function test_setup_page_renders_and_stores_temp_secret(): void
    {
        $this->actingAs($this->userWithoutSecret());

        $this->get('/2fa/setup')->assertOk()->assertViewIs('auth.2fa.setup');
        $this->assertNotEmpty(session('2fa_temp_secret'));
    }

    public function test_setup_with_valid_code_saves_secret_and_passes_session(): void
    {
        $user = $this->userWithoutSecret();
        $secret = $this->g2fa->generateSecretKey();

        $this->actingAs($user)
            ->withSession(['2fa_temp_secret' => $secret, '2fa_intended' => '/dashboard'])
            ->post('/2fa/setup', ['one_time_password' => $this->g2fa->getCurrentOtp($secret)])
            ->assertRedirect('/dashboard');

        $user->refresh();
        $this->assertSame($secret, $user->google2fa_secret);
        $this->assertTrue($user->google2fa_enabled);
        $this->assertTrue(session('2fa_passed'));
        $this->assertNull(session('2fa_temp_secret'));
    }

    public function test_setup_with_wrong_code_is_rejected(): void
    {
        $user = $this->userWithoutSecret();
        $secret = $this->g2fa->generateSecretKey();
        $wrong = str_pad((string) (((int) $this->g2fa->getCurrentOtp($secret) + 500000) % 1000000), 6, '0', STR_PAD_LEFT);

        $this->actingAs($user)
            ->withSession(['2fa_temp_secret' => $secret])
            ->post('/2fa/setup', ['one_time_password' => $wrong])
            ->assertSessionHas('error');

        $this->assertFalse($user->fresh()->hasTwoFactorSecretStored());
        $this->assertNotTrue(session('2fa_passed'));
    }

    public function test_setup_without_temp_secret_redirects_back_to_setup(): void
    {
        $this->actingAs($this->userWithoutSecret())
            ->post('/2fa/setup', ['one_time_password' => '123456'])
            ->assertRedirect(route('2fa.setup'));
    }

    public function test_setup_validates_code_format(): void
    {
        $this->actingAs($this->userWithoutSecret())
            ->post('/2fa/setup', ['one_time_password' => 'abc'])
            ->assertSessionHasErrors('one_time_password');
    }

    public function test_enrolled_user_cannot_view_setup_page(): void
    {
        $this->actingAs($this->userWithSecret());

        $this->get('/2fa/setup')->assertRedirect(route('2fa.verify'));
    }

    /** bug.md M20: a stale temp secret must never overwrite an enrolled secret. */
    public function test_stale_temp_secret_cannot_overwrite_existing_secret(): void
    {
        $original = $this->g2fa->generateSecretKey();
        $user = $this->userWithSecret($original);
        $attacker = $this->g2fa->generateSecretKey();

        $this->actingAs($user)
            ->withSession(['2fa_temp_secret' => $attacker])
            ->post('/2fa/setup', ['one_time_password' => $this->g2fa->getCurrentOtp($attacker)])
            ->assertRedirect(route('2fa.verify'));

        $this->assertSame($original, $user->fresh()->google2fa_secret);
        $this->assertNotTrue(session('2fa_passed'));
    }

    // --------------------------------------------------------------- verify

    public function test_enrolled_user_without_session_flag_is_sent_to_verify(): void
    {
        $this->actingAs($this->userWithSecret());

        $this->get('/dashboard')->assertRedirect(route('2fa.verify'));
        $this->get('/support')->assertRedirect(route('2fa.verify'));
    }

    public function test_session_flag_false_does_not_pass_middleware(): void
    {
        $this->actingAs($this->userWithSecret())->withSession(['2fa_passed' => false]);

        $this->get('/dashboard')->assertRedirect(route('2fa.verify'));
    }

    /** bug.md M19: notification routes must also require 2FA. */
    public function test_notification_routes_require_2fa(): void
    {
        $this->actingAs($this->userWithSecret());

        $this->get('/notifications')->assertRedirect(route('2fa.verify'));
        $this->get('/notifications/unread-count')->assertRedirect(route('2fa.verify'));
        $this->post('/notifications/read-all')->assertRedirect(route('2fa.verify'));
    }

    public function test_verify_page_renders_for_enrolled_user(): void
    {
        $this->actingAs($this->userWithSecret());

        $this->get('/2fa/verify')->assertOk()->assertViewIs('auth.2fa.verify');
    }

    public function test_verify_page_redirects_unenrolled_user_to_setup(): void
    {
        $this->actingAs($this->userWithoutSecret());

        $this->get('/2fa/verify')->assertRedirect(route('2fa.setup'));
        $this->post('/2fa/verify', ['one_time_password' => '123456'])->assertRedirect(route('2fa.setup'));
    }

    public function test_verify_with_valid_code_passes_session(): void
    {
        $secret = $this->g2fa->generateSecretKey();
        $user = $this->userWithSecret($secret);

        $this->actingAs($user)
            ->withSession(['2fa_intended' => '/profile'])
            ->post('/2fa/verify', ['one_time_password' => $this->g2fa->getCurrentOtp($secret)])
            ->assertRedirect('/profile');

        $this->assertTrue(session('2fa_passed'));
        $this->get('/dashboard')->assertOk();
    }

    public function test_verify_with_wrong_code_is_rejected(): void
    {
        $secret = $this->g2fa->generateSecretKey();
        $user = $this->userWithSecret($secret);
        $wrong = str_pad((string) (((int) $this->g2fa->getCurrentOtp($secret) + 500000) % 1000000), 6, '0', STR_PAD_LEFT);

        $this->actingAs($user)
            ->post('/2fa/verify', ['one_time_password' => $wrong])
            ->assertSessionHas('error');

        $this->assertNotTrue(session('2fa_passed'));
    }

    /** bug.md L12: an accepted OTP cannot be replayed. */
    public function test_otp_cannot_be_replayed(): void
    {
        $secret = $this->g2fa->generateSecretKey();
        $user = $this->userWithSecret($secret);
        $otp = $this->g2fa->getCurrentOtp($secret);

        $this->actingAs($user)->post('/2fa/verify', ['one_time_password' => $otp])->assertSessionMissing('error');

        $this->withSession(['2fa_passed' => false])
            ->post('/2fa/verify', ['one_time_password' => $otp])
            ->assertSessionHas('error');
        $this->assertFalse(session('2fa_passed'));
    }

    public function test_verify_routes_require_login(): void
    {
        $this->get('/2fa/verify')->assertRedirect('/login');
        $this->get('/2fa/setup')->assertRedirect('/login');
        $this->post('/2fa/verify', ['one_time_password' => '123456'])->assertRedirect('/login');
    }

    public function test_verify_endpoint_is_throttled(): void
    {
        $this->actingAs($this->userWithSecret());

        for ($i = 0; $i < 5; $i++) {
            $this->post('/2fa/verify', ['one_time_password' => '000000']);
        }

        $this->post('/2fa/verify', ['one_time_password' => '000000'])->assertStatus(429);
    }

    // --------------------------------------------------------------- locked

    /** bug.md H13: undecryptable secret = locked, never re-enrollment. */
    public function test_locked_user_cannot_open_setup_page(): void
    {
        $user = $this->lockedUser();
        $this->assertTrue($user->isTwoFactorLocked());

        $this->actingAs($user)->get('/2fa/setup')
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', User::twoFactorLockedMessage());

        $this->assertGuest();
    }

    public function test_locked_user_cannot_enroll_new_secret(): void
    {
        $user = $this->lockedUser();
        $attacker = $this->g2fa->generateSecretKey();

        $this->actingAs($user)
            ->withSession(['2fa_temp_secret' => $attacker])
            ->post('/2fa/setup', ['one_time_password' => $this->g2fa->getCurrentOtp($attacker)])
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertSame('garbage-not-decryptable', DB::table('users')->where('id', $user->id)->value('google2fa_secret'));
    }

    public function test_locked_user_cannot_verify(): void
    {
        $this->actingAs($this->lockedUser());

        $this->get('/2fa/verify')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_locked_user_is_logged_out_by_middleware(): void
    {
        $this->actingAs($this->lockedUser())->withSession(['2fa_passed' => true]);

        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_locked_user_gets_423_on_json_request(): void
    {
        $this->actingAs($this->lockedUser())->withSession(['2fa_passed' => true]);

        $this->getJson('/notifications/unread-count')->assertStatus(423);
    }
}
