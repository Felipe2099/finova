<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionHelper
{
    /**
     * Check if the current user has any of the given permissions
     *
     * @param array|string $permissions
     * @param string|null $guard
     * @return bool
     */
    public static function hasAnyPermission($permissions, $guard = null)
    {
        if (!Auth::guard($guard)->check()) {
            return false;
        }

        $user = Auth::guard($guard)->user();
        return $user->hasAnyPermission($permissions);
    }

    /**
     * Check if the current user has all of the given permissions
     *
     * @param array|string $permissions
     * @param string|null $guard
     * @return bool
     */
    public static function hasAllPermissions($permissions, $guard = null)
    {
        if (!Auth::guard($guard)->check()) {
            return false;
        }

        $user = Auth::guard($guard)->user();
        return $user->hasAllPermissions($permissions);
    }

    /**
     * Check if the current user has any of the given roles
     *
     * @param array|string $roles
     * @param string|null $guard
     * @return bool
     */
    public static function hasAnyRole($roles, $guard = null)
    {
        if (!Auth::guard($guard)->check()) {
            return false;
        }

        $user = Auth::guard($guard)->user();
        return $user->hasAnyRole($roles);
    }

    /**
     * Check if the current user has permission for a specific team
     *
     * @param string $permission
     * @param int $teamId
     * @param string|null $guard
     * @return bool
     */
    public static function hasTeamPermission($permission, $teamId, $guard = null)
    {
        if (!Auth::guard($guard)->check()) {
            return false;
        }

        $user = Auth::guard($guard)->user();
        return $user->hasTeamPermission($teamId, $permission);
    }

    /**
     * Get all permissions for a specific module
     *
     * @param string $module
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getModulePermissions($module)
    {
        return Permission::where('name', 'like', $module . '.%')->get();
    }

    /**
     * Create a new role with given permissions
     *
     * @param string $name
     * @param array $permissions
     * @param int|null $teamId
     * @return Role
     */
    public static function createRole($name, $permissions = [], $teamId = null)
    {
        $role = Role::create(['name' => $name, 'team_id' => $teamId]);
        
        if (!empty($permissions)) {
            $role->givePermissionTo($permissions);
        }

        return $role;
    }

    /**
     * Get all available permissions grouped by module
     *
     * @return array
     */
    public static function getAllPermissionsGrouped()
    {
        $permissions = Permission::all();
        $grouped = [];

        foreach ($permissions as $permission) {
            $module = explode('.', $permission->name)[0];
            $grouped[$module][] = $permission;
        }

        return $grouped;
    }
} 