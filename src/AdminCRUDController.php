<?php

namespace Imtigger\LaravelACLCRUD;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Imtigger\LaravelCRUD\CRUDController;
use Kris\LaravelFormBuilder\FormBuilderTrait;

class AdminCRUDController extends CRUDController
{
    use FormBuilderTrait;

    protected $viewPrefix = 'admin.admin';
    protected $routePrefix = 'admin.admin';
    protected $entityName = 'backend.entity.admin';
    protected $roleClass = \App\Models\Role::class;
    protected $entityClass = \App\Models\Admin::class;
    protected $formClass = \App\Forms\AdminForm::class;
    protected $with = ['roles'];
    protected $guard = 'admin';
    protected $suRedirect = '/admin';

    protected $suButtonIconClass = 'fa fa-users';
    protected $suButtonClass = 'btn btn-xs btn-warning';
    protected $suButtonTitle = 'laravel-acl-crud::ui.button.switch_user';
    protected $suButtonText = 'laravel-acl-crud::ui.button.switch_user';

    public $isDeletable = true;

    public static function routes($prefix, $controller, $as)
    {
        parent::routes($prefix, $controller, $as);
        Route::get("{$prefix}/su/{id}", ['as' => "{$as}.su", 'uses' => "{$controller}@switchUser"]);
    }

    public function index() {
        $builder = ($this->roleClass)::query();
        if (!empty($this->rolesShowOnly)) {
            $builder->whereIn('roles.name', $this->rolesShowOnly);
        }

        $roles = $builder->get();
        $role_options = [];
        foreach ($roles As $role) {
            $role_options[] = ['key' => $role->id, 'value' => $role->display_name];
        }

        $this->data['role_options'] = json_encode($role_options);

        return parent::index();
    }

    /**
     * Trigger when store method
     * Override this method to add additinal operations
     *
     * @return Model
     */
    protected function storeSave() {
        Request::merge(['password' => Hash::make(Request::input('password'))]);

        $entity = parent::storeSave();

        $entity->roles()->sync($this->processRole($entity));
        $entity->permissions()->sync($this->processPermission($entity));

        return $entity;
    }

    /**
     * Trigger when update method
     * Override this method to add additinal operations
     *
     * @param Model $entity
     * @return Model $entity
     */
    protected function updateSave($entity) {
        if (Request::input('password')) {
            Request::merge(['password' => Hash::make(Request::input('password'))]);
        } else {
            Request::replace(Request::except(['password']));
        }

        $entity = parent::updateSave($entity);

        $entity->roles()->sync($this->processRole($entity));
        $entity->permissions()->sync($this->processPermission($entity));

        return $entity;
    }

    /**
     * Switch user, login as another user
     *
     * @param integer $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function switchUser($id) {
        if (!Auth::user()->hasPermission('su')) {
            abort(403);
        }

        Auth::guard($this->guard)->login(($this->entityClass)::whereId($id)->firstOrFail());

        return redirect($this->suRedirect);
    }

    /**
     * Construct Datatable object
     *
     * @param $items
     * @return \Yajra\DataTables\DataTableAbstract
     * @throws \Exception
     */
    protected function ajaxListDataTable($items)
    {
        $datatable = parent::ajaxListDataTable($items);

        $datatable->addColumn('role_names', function ($item) {
            return $item->roles->implode('display_name', ', ');
        });

        $datatable->filterColumn('role_names', function ($query, $keyword) {
            $query->whereHas('roles', function ($query) use ($keyword) {
                $query->whereId($keyword);
            });
        });

        return $datatable;
    }

    /**
     *
     *
     * @param null $entity
     * @return \Illuminate\Support\Collection
     */
    protected function processRole($entity = null)
    {
        $submittedRoles = collect(Request::input('roles', []));
        
        if (Auth::user()->hasRole('root')) return $submittedRoles;
        
        // Removed submitted roles that current user do not have
        $myRoles = Auth::user()->roles->pluck('id');
        $filteredRoles = $submittedRoles->intersect($myRoles);

        if ($entity === null) return $filteredRoles;

        // If current user don't have current permission, protect it so it's persist after save
        $oldRoles = $entity->roles->pluck('id');
        $maskedRoles = $oldRoles->diff($myRoles); // Old permissions current user don't have
        $finalRoles = $filteredRoles->merge($maskedRoles);

        return $finalRoles;
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

    /**
     * Extra DataTables action field, append string after default actions
     *
     * @param $item
     * @return string
     */
    protected function ajaxListActions($item)
    {
        return parent::ajaxListActions($item) . (Auth::user()->hasPermission('su') ? '<a title="' . trans($this->suButtonTitle) . '" href="' . route("{$this->routePrefix}.su", [$item->id]) .'" class="' . $this->suButtonClass . '"><i class="' . trans($this->suButtonIconClass) . '"></i> ' . trans($this->suButtonText) . '</a> ' : '');
    }
}
