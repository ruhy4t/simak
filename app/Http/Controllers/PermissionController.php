<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermissionController extends Controller
{
    /**
     * Display permission management page for a specific user
     */
    public function edit(User $user)
    {
        $this->authorize('managePermissions', $user);

        $permissions = Permission::allGrouped();
        $roleDefaults = Permission::getDefaultsForRole($user->role);
        $userOverrides = $user->permissionOverrides()
            ->get()
            ->mapWithKeys(fn($p) => [$p->id => $p->pivot->is_granted]);

        return view('admin.permissions.edit', compact(
            'user',
            'permissions',
            'roleDefaults',
            'userOverrides'
        ));
    }

    /**
     * Update user's permission overrides
     */
    public function update(Request $request, User $user)
    {
        $this->authorize('managePermissions', $user);

        $validated = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'in:default,grant,revoke',
        ]);

        $permissions = $validated['permissions'] ?? [];

        // Process each permission
        foreach ($permissions as $permissionId => $action) {
            $permission = Permission::find($permissionId);
            if (!$permission)
                continue;

            if ($action === 'default') {
                // Remove override, revert to role default
                $user->permissionOverrides()->detach($permissionId);
            } elseif ($action === 'grant') {
                // Explicitly grant
                $user->permissionOverrides()->syncWithoutDetaching([
                    $permissionId => ['is_granted' => true]
                ]);
            } elseif ($action === 'revoke') {
                // Explicitly revoke
                $user->permissionOverrides()->syncWithoutDetaching([
                    $permissionId => ['is_granted' => false]
                ]);
            }
        }

        return redirect()
            ->route('permissions.edit', $user)
            ->with('success', 'Permission berhasil diperbarui untuk ' . $user->username);
    }

    /**
     * API: Get current user's permissions
     */
    public function myPermissions(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => $user->username,
            'role' => $user->role,
            'permissions' => $user->getEffectivePermissions(),
        ]);
    }

    /**
     * API: Check if current user has specific permission
     */
    public function check(Request $request, string $permission)
    {
        $user = $request->user();

        return response()->json([
            'permission' => $permission,
            'has_permission' => $user->hasPermission($permission),
        ]);
    }
}
