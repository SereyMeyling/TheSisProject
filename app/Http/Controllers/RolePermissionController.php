<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    /**
     * Fetch all created roles with their associated permissions.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\View|JsonResponse
     */
    public function index(Request $request)
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'roles'       => $roles,
                'permissions' => $permissions,
            ]);
        }

        return view('roles.index', compact('roles', 'permissions'));
    }

    /**
     * Create a new role in the system.
     *
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function storeRole(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
        ], [
            'name.unique' => 'This role already exists in the system.',
        ]);

        $role = Role::create([
            'name'       => strtolower(trim($request->name)),
            'guard_name' => 'web',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Role '{$role->name}' created successfully.",
                'data'    => $role,
            ], 201);
        }

        return redirect()->back()->with('success', "Role '{$role->name}' created successfully.");
    }

    /**
     * Create a new permission in the system.
     *
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function storePermission(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name'],
        ], [
            'name.unique' => 'This permission already exists in the system.',
        ]);

        $permission = Permission::create([
            'name'       => strtolower(trim($request->name)),
            'guard_name' => 'web',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Permission '{$permission->name}' created successfully.",
                'data'    => $permission,
            ], 201);
        }

        return redirect()->back()->with('success', "Permission '{$permission->name}' created successfully.");
    }

    /**
     * Assign or sync an array of permissions to a specific role.
     *
     * @param Request $request
     * @param Role $role
     * @return RedirectResponse|JsonResponse
     */
    public function assignPermissionsToRole(Request $request, Role $role)
    {
        $request->validate([
            'permissions'   => ['required', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ], [
            'permissions.required' => 'Please select at least one permission.',
            'permissions.*.exists' => 'One or more selected permissions do not exist.',
        ]);

        // Sync array of permission names to the role
        $role->syncPermissions($request->permissions);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Permissions synced successfully for role '{$role->name}'.",
                'data'    => $role->load('permissions'),
            ]);
        }

        return redirect()->back()->with('success', "Permissions synced successfully for role '{$role->name}'.");
    }
}
