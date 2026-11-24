<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * ProfileController: edit page, profile update, password change, avatar serving.
 */
class ProfileTest extends TestCase
{
    use DatabaseTransactions;

    private function payload(User $user, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Updated Name',
            'email' => $user->email,
            'username' => $user->username ?: 'p_' . Str::lower(Str::random(8)),
            'phone' => '012345678',
        ], $overrides);
    }

    public function test_every_role_can_open_profile_page(): void
    {
        foreach (['admin', 'doctor', 'nurse', 'pharmacist', 'cashier', 'lab_technician'] as $role) {
            $this->actingAsRole($role);
            $this->get('/profile')->assertOk()->assertViewIs('form.profile.edit');
        }
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/profile')->assertRedirect('/login');
        $this->put('/profile', [])->assertRedirect('/login');
    }

    public function test_user_can_update_profile(): void
    {
        $user = $this->actingAsRole('nurse', ['username' => 'nurse_' . Str::lower(Str::random(8))]);
        $deptId = DB::table('departments')->value('department_id');

        $this->from('/profile')->put('/profile', $this->payload($user, [
            'email' => 'changed_' . Str::lower(Str::random(6)) . '@example.test',
            'department_id' => $deptId,
            'specialization' => 'Triage',
        ]))->assertRedirect('/profile')->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('012345678', $user->phone);
        $this->assertSame('Triage', $user->specialization);
        $this->assertEquals($deptId, $user->department_id);
    }

    public function test_profile_update_ajax_returns_json(): void
    {
        $user = $this->actingAsRole('doctor', ['username' => 'doc_' . Str::lower(Str::random(8))]);

        $this->put('/profile', $this->payload($user), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('name', 'Updated Name');
    }

    public function test_profile_update_validation(): void
    {
        $other = User::factory()->create(['username' => 'other_' . Str::lower(Str::random(8))]);
        $user = $this->actingAsRole('nurse', ['username' => 'me_' . Str::lower(Str::random(8))]);

        $this->put('/profile', $this->payload($user, ['email' => $other->email]))->assertSessionHasErrors('email');
        $this->put('/profile', $this->payload($user, ['username' => $other->username]))->assertSessionHasErrors('username');
        $this->put('/profile', $this->payload($user, ['name' => '']))->assertSessionHasErrors('name');
        $this->put('/profile', $this->payload($user, ['department_id' => 999999999]))->assertSessionHasErrors('department_id');
    }

    /** bug.md: a user cannot escalate privileges or tamper with 2FA via profile update. */
    public function test_profile_update_cannot_escalate_role_or_touch_2fa(): void
    {
        $user = $this->actingAsRole('nurse', ['username' => 'esc_' . Str::lower(Str::random(8))]);
        $rawSecretBefore = DB::table('users')->where('id', $user->id)->value('google2fa_secret');
        $passwordBefore = DB::table('users')->where('id', $user->id)->value('password');

        $this->put('/profile', $this->payload($user, [
            'role' => 'admin',
            'roles' => ['admin'],
            'google2fa_secret' => null,
            'google2fa_enabled' => 0,
            'password' => 'NewPassword123',
            'id' => 1,
            'avatar_mime' => 'text/html',
        ]))->assertSessionHasNoErrors();

        $fresh = $user->fresh();
        $this->assertFalse($fresh->hasRole('admin'));
        $this->assertTrue($fresh->hasRole('nurse'));
        $this->assertSame($rawSecretBefore, DB::table('users')->where('id', $user->id)->value('google2fa_secret'));
        $this->assertSame($passwordBefore, DB::table('users')->where('id', $user->id)->value('password'));
        $this->assertNotSame('text/html', $fresh->avatar_mime);
        $this->assertNotNull(User::find($user->id));
    }

    // --------------------------------------------------------------- avatar

    public function test_avatar_upload_and_serve(): void
    {
        $user = $this->actingAsRole('nurse', ['username' => 'av_' . Str::lower(Str::random(8))]);

        $this->put('/profile', $this->payload($user, [
            'avatar' => UploadedFile::fake()->image('me.png', 20, 20),
        ]))->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('image/png', $user->avatar_mime);

        $this->get("/profile/avatar/{$user->id}")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    /** bug.md H3: client-declared MIME type must not be trusted. */
    public function test_avatar_mime_comes_from_file_content_not_client(): void
    {
        $user = $this->actingAsRole('nurse', ['username' => 'mime_' . Str::lower(Str::random(8))]);
        // Keep $real alive: its temp file is removed when the object is destroyed.
        $real = UploadedFile::fake()->image('real.png', 20, 20);
        $spoofed = new UploadedFile($real->getRealPath(), 'real.png', 'text/html', null, true);

        $this->put('/profile', $this->payload($user, ['avatar' => $spoofed]))->assertSessionHasNoErrors();

        $this->assertSame('image/png', $user->fresh()->avatar_mime);
        $this->get("/profile/avatar/{$user->id}")->assertHeader('Content-Type', 'image/png');
    }

    /** bug.md H3: legacy rows with a dangerous stored MIME are served as an image. */
    public function test_avatar_with_stored_html_mime_is_not_served_as_html(): void
    {
        $this->actingAsRole('nurse');
        $victim = User::factory()->create();
        DB::table('users')->where('id', $victim->id)->update([
            'avatar' => base64_encode('<script>alert(1)</script>'),
            'avatar_mime' => 'text/html',
        ]);

        $res = $this->get("/profile/avatar/{$victim->id}")->assertOk();
        $this->assertSame('image/png', $res->headers->get('Content-Type'));
        $res->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_avatar_rejects_non_image_upload(): void
    {
        $user = $this->actingAsRole('nurse', ['username' => 'bad_' . Str::lower(Str::random(8))]);

        $this->put('/profile', $this->payload($user, [
            'avatar' => UploadedFile::fake()->create('x.html', 1, 'text/html'),
        ]))->assertSessionHasErrors('avatar');
    }

    public function test_avatar_404_when_user_has_none(): void
    {
        $this->actingAsRole('nurse');
        $u = User::factory()->create();

        $this->get("/profile/avatar/{$u->id}")->assertNotFound();
    }

    // ------------------------------------------------------------- password

    public function test_user_can_change_password(): void
    {
        $user = $this->actingAsRole('cashier');

        $this->from('/profile')->put('/profile/password', [
            'current_password' => 'password',
            'password' => 'BrandNewPass1',
            'password_confirmation' => 'BrandNewPass1',
        ])->assertRedirect('/profile')->assertSessionHas('success');

        $this->assertTrue(Hash::check('BrandNewPass1', $user->fresh()->password));
    }

    public function test_change_password_requires_correct_current_password(): void
    {
        $user = $this->actingAsRole('cashier');

        $this->put('/profile/password', [
            'current_password' => 'wrong-one',
            'password' => 'BrandNewPass1',
            'password_confirmation' => 'BrandNewPass1',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_change_password_validation(): void
    {
        $this->actingAsRole('cashier');

        $this->put('/profile/password', [
            'current_password' => 'password',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->put('/profile/password', [
            'current_password' => 'password',
            'password' => 'LongEnough123',
            'password_confirmation' => 'Different123',
        ])->assertSessionHasErrors('password');
    }

    public function test_change_password_ajax_wrong_current_returns_422(): void
    {
        $this->actingAsRole('cashier');

        $this->put('/profile/password', [
            'current_password' => 'wrong-one',
            'password' => 'BrandNewPass1',
            'password_confirmation' => 'BrandNewPass1',
        ], ['X-Requested-With' => 'XMLHttpRequest'])->assertStatus(422);
    }
}
