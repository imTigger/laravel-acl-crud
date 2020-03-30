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

        $entity->syncPermissions(AclHelper::processPermission(Request::input('permissions', []), $entity));

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
        $entity->syncPermissions(AclHelper::processPermission(Request::input('permissions', []), $entity));

        return $entity;
    }
}
