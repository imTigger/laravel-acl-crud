<?php
namespace Imtigger\LaravelACLCRUD;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Imtigger\LaravelCRUD\CRUDController;

class RoleCRUDController extends CRUDController
{
    protected $viewPrefix = 'admin.role';
    protected $routePrefix = 'admin.role';
    protected $entityName = 'Role';
    protected $entityClass = \App\Models\Role::class;
    protected $formClass = \App\Forms\RoleForm::class;

    /**
     * Trigger when store method
     * Override this method to add additional operations
     *
     * @return Model
     */
    protected function storeSave() {
        $entity = parent::storeSave();

        $entity->syncPermissions($this->processPermission());

        return $entity;
    }

    /**
     * Trigger when update method
     * Override this method to add additional operations
     *
     * @param Model $entity
     * @return Model $entity
     */
    protected function updateSave($entity) {
        $entity->syncPermissions($this->processPermission($entity));

        return $entity;
    }

    protected function processPermission($entity = null)
    {        
        $submittedPermissions = collect(Request::input('permissions', []));
        
        if (Auth::user()->hasRole('root')) return $submittedPermissions;
        
        // Removed submitted permissions that current user do not have
        $myPermissions = Auth::user()->allPermissions()->pluck('id');
        $filteredPermissions = $submittedPermissions->intersect($myPermissions);

        if ($entity === null) return $filteredPermissions;

        // If current user don't have current permission, protect it so it's persist after save
        $oldPermissions = $entity->permissions->pluck('id');
        $maskedPermissions = $oldPermissions->diff($myPermissions); // Old permissions current user don't have
        $finalPermissions = $filteredPermissions->merge($maskedPermissions);

        return $finalPermissions;
    }
}
