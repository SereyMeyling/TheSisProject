<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * RolePermissionController: list roles, create roles/permissions, sync permissions.
 */
class RolePermissionTest extends TestCase
{
    use DatabaseTransactions;

    private const NON_ADMIN_ROLES = ['doctor', 'nurse', 'pharmacist', 'cashier', 'lab_technician'];

    private function uniqueName(string $prefix): string
    {
        return $prefix . '_' . Str::lower(Str::random(8));
    }

    public function test_admin_gets_roles_and_permissions_as_json(): void
    {
        $this->actingAsRole('admin');

        $this->getJson('/roles')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['roles' => [['id', 'name', 'permissions']], 'permissions']);
    }

    /** bug.md M23: /roles used to render a missing view (500). */
    public function test_roles_html_request_redirects_to_user_page(): void
    {
        $this->actingAsRole('admin');

        $this->get('/roles')->assertRedirect(route('user.index'));
    }

    public function test_admin_can_create_role(): void
    {
        $this->actingAsRole('admin');
        $name = $this->uniqueName('Role');

        $this->postJson('/roles/store', ['name' => "  {$name} "])
            ->assertStatus(201)
            ->assertJsonPath('data.name', strtolower($name));

        $this->assertNotNull(Role::where('name', strtolower($name))->first());
    }

    public function test_duplicate_role_is_rejected(): void
    {
        $this->actingAsRole('admin');

        $this->postJson('/roles/store', ['name' => 'admin'])
            ->assertStatus(422)->assertJsonValidationErrors('name');
        $this->postJson('/roles/store', [])
            ->assertStatus(422)->assertJsonValidationErrors('name');
    }

    public function test_admin_can_create_permission(): void
    {
        $this->actingAsRole('admin');
        $name = $this->uniqueName('perm');

        $this->postJson('/roles/permission/store', ['name' => $name])->assertStatus(201);

        $this->assertNotNull(Permission::where('name', $name)->first());

        $this->postJson('/roles/permission/store', ['name' => $name])
            ->assertStatus(422)->assertJsonValidationErrors('name');
    }

    public function test_admin_can_sync_permissions_to_role(): void
    {
        $this->actingAsRole('admin');
        $role = Role::create(['name' => $this->uniqueName('role'), 'guard_name' => 'web']);
        $p1 = Permission::create(['name' => $this->uniqueName('p'), 'guard_name' => 'web']);
        $p2 = Permission::create(['name' => $this->uniqueName('p'), 'guard_name' => 'web']);

        $this->postJson("/roles/{$role->id}/permissions", ['permissions' => [$p1->name, $p2->name]])
            ->assertOk()->assertJsonPath('success', true);

        $this->assertEqualsCanonicalizing([$p1->name, $p2->name], $role->fresh()->permissions->pluck('name')->all());

        // Sync replaces, not appends
        $this->postJson("/roles/{$role->id}/permissions", ['permissions' => [$p2->name]])->assertOk();
        $this->assertSame([$p2->name], $role->fresh()->permissions->pluck('name')->all());
    }

    public function test_sync_rejects_unknown_or_empty_permissions(): void
    {
        $this->actingAsRole('admin');
        $role = Role::create(['name' => $this->uniqueName('role'), 'guard_name' => 'web']);

        $this->postJson("/roles/{$role->id}/permissions", ['permissions' => ['does-not-exist-' . Str::random(6)]])
            ->assertStatus(422);
        $this->postJson("/roles/{$role->id}/permissions", [])
            ->assertStatus(422)->assertJsonValidationErrors('permissions');
    }

    public function test_sync_to_missing_role_is_404(): void
    {
        $this->actingAsRole('admin');

        $this->postJson('/roles/999999999/permissions', ['permissions' => ['manage-billing-settings']])
            ->assertNotFound();
    }

    public function test_non_admins_cannot_manage_roles_or_permissions(): void
    {
        $ownRole = Role::findByName('doctor');
        $before = $ownRole->permissions->pluck('name')->sort()->values()->all();

        foreach (self::NON_ADMIN_ROLES as $role) {
            $this->actingAsRole($role);

            $this->getJson('/roles')->assertForbidden();
            $this->postJson('/roles/store', ['name' => $this->uniqueName('evil')])->assertForbidden();
            $this->postJson('/roles/permission/store', ['name' => $this->uniqueName('evil')])->assertForbidden();
            $this->postJson("/roles/{$ownRole->id}/permissions", ['permissions' => ['manage-billing-settings']])
                ->assertForbidden();
        }

        $after = $ownRole->fresh()->permissions->pluck('name')->sort()->values()->all();
        $this->assertSame($before, $after);
        $this->assertSame(0, Role::where('name', 'like', 'evil_%')->count());
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/roles')->assertRedirect('/login');
        $this->post('/roles/store', ['name' => 'x'])->assertRedirect('/login');
    }
}
