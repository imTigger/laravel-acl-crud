<?php

namespace Imtigger\LaravelACLCRUD;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Input;
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
        Input::merge(['password' => Hash::make(Input::get('password'))]);

        $entity = parent::storeSave();
        
        $submittedRoles = collect(Input::get('roles', []));
        $submittedPermissions = collect(Input::get('permissions', []));

        $entity->roles()->sync($submittedRoles);
        $entity->permissions()->sync($submittedPermissions);

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
        if (Input::get('password')) {
            Input::merge(['password' => Hash::make(Input::get('password'))]);
        } else {
            Input::replace(Input::except(['password']));
        }
        
        $submittedRoles = collect(Input::get('roles', []));
        $submittedPermissions = collect(Input::get('permissions', []));
        
        // If current user don't have current permission, protect it from removing
        $oldPermissions = $entity->permissions->pluck('id');
        $myPermissions = Auth::user()->allPermissions()->pluck('id');
        $maskedPermissions = $oldPermissions->diff($myPermissions);
        $finalPermissions = $submittedPermissions->merge($maskedPermissions);
        
        $entity = parent::updateSave($entity);

        $entity->roles()->sync($submittedRoles);
        $entity->permissions()->sync($finalPermissions);

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

        Auth::login(($this->entityClass)::whereId($id)->firstOrFail());

        return redirect('/');
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
     * Extra DataTables action field, append string after default actions
     *
     * @param $item
     * @return string
     */
    protected function ajaxListActions($item)
    {
        return parent::ajaxListActions($item) . (Auth::user()->hasPermission('su') ? '<a href="' . route("{$this->routePrefix}.su", [$item->id]) .'" class="btn btn-xs btn-warning"><i class="fa fa-users"></i> ' . trans('laravel-acl-crud::ui.button.switch_user') . '</a> ' : '');
    }
}