<?php
namespace Imtigger\LaravelACLCRUD;

use Illuminate\Support\Facades\Auth;
use App\Models\Role;

class AclHelper
{
    public static function processRole($input, $entity = null)
    {
        $submittedRoleIds = collect($input);

        // Removed submitted roles that current user do not have all permissions of it
        $myPermissions = Auth::user()->allPermissions()->pluck('id');
        $myAccessibleRoles = Role::get()->filter(function ($role, $key) use ($myPermissions) {
            return self::hasAllPermissionsOfRole($myPermissions, $role);
        })->pluck('id');
        $filteredRoles = $myAccessibleRoles->intersect($submittedRoleIds);

        if ($entity === null) return $filteredRoles;

        // If current user don't have current permission, protect it so it's persist after save
        $oldRoles = $entity->roles->pluck('id');
        $maskedRoles = $oldRoles->diff($myAccessibleRoles); // Old permissions current user don't have

        return $filteredRoles->merge($maskedRoles);
    }

    public static function processPermission($input, $entity = null)
    {
        $submittedPermissions = collect($input);

        // Removed submitted permissions that current user do not have
        $myPermissions = Auth::user()->allPermissions()->pluck('id');
        $filteredPermissions = $submittedPermissions->intersect($myPermissions);

        if ($entity === null) return $filteredPermissions;

        // If current user don't have current permission, protect it so it's persist after save
        $oldPermissions = $entity->permissions->pluck('id');
        $maskedPermissions = $oldPermissions->diff($myPermissions); // Old permissions current user don't have

        return $filteredPermissions->merge($maskedPermissions);
    }

    public static function hasAllPermissionsOfRole($_permissionIds, $role)
    {
        $permissionIds = collect($_permissionIds);
        $rolePermissionIds = $role->permissions->pluck('id');
        return $rolePermissionIds->diff($permissionIds)->count() === 0;
    }
}
