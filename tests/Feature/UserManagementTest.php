<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * UserController: list/create users, change role, delete, admin 2FA reset.
 */
class UserManagementTest extends TestCase
{
    use DatabaseTransactions;

    private const NON_ADMIN_ROLES = ['doctor', 'nurse', 'pharmacist', 'cashier', 'lab_technician'];

    private function targetUser(string $role = 'nurse'): User
    {
        $u = User::factory()->create(['username' => 'tgt_' . Str::random(8)]);
        $u->assignRole($role);
        $u->google2fa_secret = (new Google2FA())->generateSecretKey();
        $u->google2fa_enabled = true;
        $u->save();

        return $u;
    }

    private function newUserPayload(array $overrides = []): array
    {
        $s = Str::lower(Str::random(8));

        return array_merge([
            'name' => 'Test User ' . $s,
            'email' => "new_{$s}@example.test",
            'username' => "new_{$s}",
            'password' => 'Secret123!',
            'role' => 'doctor',
        ], $overrides);
    }

    // --------------------------------------------------------------- access

    public function test_admin_can_view_user_list(): void
    {
        $this->actingAsRole('admin');

        $this->get('/user')->assertOk()->assertViewIs('form.user.user');
    }

    public function test_admin_can_search_users_via_ajax(): void
    {
        $admin = $this->actingAsRole('admin', ['username' => 'srch_' . Str::random(8)]);

        $this->getJson('/user?search=' . urlencode($admin->username), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonStructure(['html', 'total']);
    }

    public function test_user_list_json_does_not_leak_2fa_secret(): void
    {
        $this->actingAsRole('admin');
        $this->targetUser();

        $res = $this->getJson('/user')->assertOk();
        $this->assertStringNotContainsString('google2fa_secret', $res->getContent());
        $this->assertStringNotContainsString('"password"', $res->getContent());
    }

    public function test_non_admins_cannot_use_user_management(): void
    {
        $target = $this->targetUser();

        foreach (self::NON_ADMIN_ROLES as $role) {
            $this->actingAsRole($role);

            $this->get('/user')->assertForbidden();
            $this->post('/user/store', $this->newUserPayload(['role' => 'admin']))->assertForbidden();
            $this->put("/user/{$target->id}/role", ['role' => 'admin'])->assertForbidden();
            $this->delete("/user/{$target->id}")->assertForbidden();
            $this->post("/user/{$target->id}/reset-2fa")->assertForbidden();
        }

        $this->assertTrue($target->fresh()->hasRole('nurse'));
        $this->assertFalse($target->fresh()->hasRole('admin'));
        $this->assertNotNull(User::find($target->id));
        $this->assertTrue($target->fresh()->hasTwoFactorSecretStored());
    }

    public function test_non_admin_cannot_promote_self_to_admin(): void
    {
        $me = $this->actingAsRole('doctor');

        $this->put("/user/{$me->id}/role", ['role' => 'admin'])->assertForbidden();

        $this->assertFalse($me->fresh()->hasRole('admin'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/user')->assertRedirect('/login');
        $this->post('/user/store', $this->newUserPayload())->assertRedirect('/login');
    }

    // ---------------------------------------------------------------- store

    public function test_admin_can_create_user_with_role(): void
    {
        $this->actingAsRole('admin');
        $payload = $this->newUserPayload();

        $this->post('/user/store', $payload)->assertRedirect(route('user.index'));

        $user = User::where('email', $payload['email'])->firstOrFail();
        $this->assertSame($payload['username'], $user->username);
        $this->assertTrue($user->hasRole('doctor'));
        $this->assertTrue(Hash::check('Secret123!', $user->password));
    }

    public function test_create_user_via_json_returns_201(): void
    {
        $this->actingAsRole('admin');

        $this->postJson('/user/store', $this->newUserPayload(['role' => 'cashier']))
            ->assertStatus(201)
            ->assertJsonPath('success', true);
    }

    public function test_create_user_ignores_mass_assigned_2fa_fields(): void
    {
        $this->actingAsRole('admin');
        $payload = $this->newUserPayload([
            'google2fa_secret' => 'ATTACKERSECRET12',
            'google2fa_enabled' => 1,
            'remember_token' => 'abc',
        ]);

        $this->post('/user/store', $payload)->assertRedirect();

        $raw = DB::table('users')->where('email', $payload['email'])->first();
        $this->assertNull($raw->google2fa_secret);
        $this->assertEquals(0, $raw->google2fa_enabled);
    }

    public function test_create_user_validation(): void
    {
        $existing = $this->targetUser();
        $this->actingAsRole('admin');

        $this->postJson('/user/store', [])->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'username', 'password', 'role']);

        $this->postJson('/user/store', $this->newUserPayload(['email' => $existing->email]))
            ->assertStatus(422)->assertJsonValidationErrors('email');

        $this->postJson('/user/store', $this->newUserPayload(['username' => $existing->username]))
            ->assertStatus(422)->assertJsonValidationErrors('username');

        $this->postJson('/user/store', $this->newUserPayload(['role' => 'superuser']))
            ->assertStatus(422)->assertJsonValidationErrors('role');

        $this->postJson('/user/store', $this->newUserPayload(['password' => 'short']))
            ->assertStatus(422)->assertJsonValidationErrors('password');
    }

    // ----------------------------------------------------------- updateRole

    public function test_admin_can_change_user_role(): void
    {
        $this->actingAsRole('admin');
        $target = $this->targetUser('nurse');

        $this->put("/user/{$target->id}/role", ['role' => 'pharmacist'])->assertRedirect(route('user.index'));

        $target = $target->fresh();
        $this->assertTrue($target->hasRole('pharmacist'));
        $this->assertFalse($target->hasRole('nurse'));
    }

    public function test_change_role_rejects_unknown_role(): void
    {
        $this->actingAsRole('admin');
        $target = $this->targetUser('nurse');

        $this->putJson("/user/{$target->id}/role", ['role' => 'god'])->assertStatus(422);
        $this->assertTrue($target->fresh()->hasRole('nurse'));
    }

    /** bug.md L13: admin cannot demote themselves. */
    public function test_admin_cannot_remove_own_admin_role(): void
    {
        $me = $this->actingAsRole('admin');

        $this->putJson("/user/{$me->id}/role", ['role' => 'nurse'])
            ->assertStatus(422)->assertJsonPath('success', false);

        $this->assertTrue($me->fresh()->hasRole('admin'));
    }

    public function test_admin_can_demote_another_admin(): void
    {
        $this->actingAsRole('admin');
        $other = $this->targetUser('admin');

        $this->putJson("/user/{$other->id}/role", ['role' => 'nurse'])->assertOk();

        $this->assertFalse($other->fresh()->hasRole('admin'));
    }

    // -------------------------------------------------------------- destroy

    public function test_admin_can_delete_user(): void
    {
        $this->actingAsRole('admin');
        $target = $this->targetUser();

        $this->delete("/user/{$target->id}")->assertRedirect(route('user.index'));
        $this->assertNull(User::find($target->id));
    }

    /** Production FKs cascade: deleting a doctor would wipe their medical records/appointments. */
    public function test_admin_cannot_delete_user_with_clinical_history(): void
    {
        $this->actingAsRole('admin');
        $doctor = $this->targetUser();

        $patientId = \Illuminate\Support\Facades\DB::table('patients')->insertGetId([
            'patient_code' => 'T-' . \Illuminate\Support\Str::random(8),
            'full_name' => 'History Patient',
            'id_card' => 'T-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(12)),
            'sex' => 'male',
            'date_of_birth' => '1990-01-01',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        \App\Models\MedicalRecord::create(['patient_id' => $patientId, 'user_id' => $doctor->id, 'visit_date' => now()]);

        $this->deleteJson("/user/{$doctor->id}")->assertStatus(422);
        $this->assertNotNull(User::find($doctor->id));
        $this->assertDatabaseHas('medical_records', ['user_id' => $doctor->id]);
    }

    /** bug.md L13: admin cannot delete self. */
    public function test_admin_cannot_delete_self(): void
    {
        $me = $this->actingAsRole('admin');

        $this->deleteJson("/user/{$me->id}")->assertStatus(422);
        $this->assertNotNull(User::find($me->id));
    }

    // ------------------------------------------------------------ reset 2FA

    public function test_admin_can_reset_user_2fa(): void
    {
        $this->actingAsRole('admin');
        $target = $this->targetUser();

        $this->from('/user')->post("/user/{$target->id}/reset-2fa")
            ->assertRedirect('/user')->assertSessionHas('success');

        $target = $target->fresh();
        $this->assertFalse($target->hasTwoFactorSecretStored());
        $this->assertFalse((bool) $target->google2fa_enabled);
    }

    public function test_admin_reset_unlocks_locked_account(): void
    {
        $this->actingAsRole('admin');
        $target = $this->targetUser();
        DB::table('users')->where('id', $target->id)->update(['google2fa_secret' => 'undecryptable']);
        $this->assertTrue($target->fresh()->isTwoFactorLocked());

        $this->post("/user/{$target->id}/reset-2fa")->assertRedirect();

        $target = $target->fresh();
        $this->assertFalse($target->isTwoFactorLocked());
        $this->assertFalse($target->hasTwoFactorSecretStored());
    }

    public function test_reset_2fa_for_missing_user_shows_error(): void
    {
        $this->actingAsRole('admin');

        $this->from('/user')->post('/user/999999999/reset-2fa')
            ->assertRedirect('/user')->assertSessionHas('error');
    }
}
