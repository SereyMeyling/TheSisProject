<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * LoginController: login by email or username, throttling, and the
 * redirect into the mandatory 2FA flow.
 */
class AuthTest extends TestCase
{
    use DatabaseTransactions;

    /** Factory password hash is for the plain text "password". */
    private const PASSWORD = 'password';

    private function makeUser(bool $withSecret = true): User
    {
        $user = User::factory()->create(['username' => 'u_' . Str::random(10)]);
        $user->assignRole('nurse');
        if ($withSecret) {
            $user->google2fa_secret = (new Google2FA())->generateSecretKey();
            $user->save();
        }

        return $user;
    }

    public function test_login_page_renders_for_guest(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_login_with_email_redirects_to_2fa_verify(): void
    {
        $user = $this->makeUser();

        $res = $this->post('/login', ['login' => $user->email, 'password' => self::PASSWORD]);

        $res->assertRedirect(route('2fa.verify'));
        $this->assertAuthenticatedAs($user);
        $this->assertFalse(session('2fa_passed'));
    }

    public function test_login_with_username_redirects_to_2fa_verify(): void
    {
        $user = $this->makeUser();

        $res = $this->post('/login', ['login' => $user->username, 'password' => self::PASSWORD]);

        $res->assertRedirect(route('2fa.verify'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_first_login_without_secret_is_forced_to_2fa_setup(): void
    {
        $user = $this->makeUser(false);

        $this->post('/login', ['login' => $user->email, 'password' => self::PASSWORD])
            ->assertRedirect(route('2fa.setup'));

        $this->assertFalse(session('2fa_passed'));
    }

    public function test_login_clears_stale_2fa_temp_secret(): void
    {
        $user = $this->makeUser(false);

        $this->withSession(['2fa_temp_secret' => 'STALESECRETVALUE'])
            ->post('/login', ['login' => $user->email, 'password' => self::PASSWORD]);

        $this->assertNull(session('2fa_temp_secret'));
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = $this->makeUser();

        $this->from('/login')
            ->post('/login', ['login' => $user->email, 'password' => 'wrong-password'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_unknown_username_is_rejected(): void
    {
        $this->post('/login', ['login' => 'no_such_user_' . Str::random(8), 'password' => 'whatever'])
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_login_requires_both_fields(): void
    {
        $this->post('/login', [])->assertSessionHasErrors(['login', 'password']);
        $this->assertGuest();
    }

    public function test_login_is_locked_out_after_five_failed_attempts(): void
    {
        $user = $this->makeUser();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['login' => $user->email, 'password' => 'bad-' . $i]);
        }

        // Even the correct password is refused while locked out.
        $res = $this->post('/login', ['login' => $user->email, 'password' => self::PASSWORD]);

        $res->assertSessionHasErrors('login');
        $throttlePrefix = strtok(trans('auth.throttle'), ':');
        $this->assertStringStartsWith($throttlePrefix, session('errors')->first('login'));
        $this->assertGuest();
    }

    public function test_locked_2fa_secret_blocks_login(): void
    {
        $user = $this->makeUser();
        // Simulate a secret encrypted with a different APP_KEY.
        DB::table('users')->where('id', $user->id)->update(['google2fa_secret' => 'not-a-valid-ciphertext']);

        $this->post('/login', ['login' => $user->email, 'password' => self::PASSWORD])
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', User::twoFactorLockedMessage());

        $this->assertGuest();
        // The stored secret must not have been replaced.
        $this->assertSame('not-a-valid-ciphertext', DB::table('users')->where('id', $user->id)->value('google2fa_secret'));
    }

    public function test_logged_in_user_cannot_see_login_page(): void
    {
        $this->actingAsRole('nurse');

        $this->get('/login')->assertRedirect();
    }

    public function test_logout_logs_user_out(): void
    {
        $this->actingAsRole('nurse');

        $this->post('/logout')->assertRedirect();
        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_password_is_not_mass_assigned_from_role_fields(): void
    {
        // User model must not allow role / 2FA fields to be mass assigned.
        $user = new User();
        $user->fill([
            'name' => 'x',
            'google2fa_secret' => 'ABC',
            'google2fa_enabled' => true,
            'remember_token' => 'tok',
        ]);

        $this->assertArrayNotHasKey('google2fa_secret', $user->getAttributes());
        $this->assertArrayNotHasKey('google2fa_enabled', $user->getAttributes());
    }
}
