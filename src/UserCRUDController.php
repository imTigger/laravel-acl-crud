<?php

namespace Imtigger\LaravelACLCRUD;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Route;
use Imtigger\LaravelCRUD\CRUDController;
use Kris\LaravelFormBuilder\FormBuilderTrait;

class UserCRUDController extends CRUDController
{
    use FormBuilderTrait;

    protected $viewPrefix = 'admin.user';
    protected $routePrefix = 'admin.user';
    protected $entityName = 'backend.entity.user';
    protected $entityClass = \App\Models\User::class;
    protected $formClass = \App\Forms\UserForm::class;
    protected $with = [];
    protected $rawColumns = ['actions'];

    protected $isDeletable = true;

    public static function routes($prefix, $controller, $as)
    {
        parent::routes($prefix, $controller, $as);
        Route::get("{$prefix}/su/{id}", ['as' => "{$as}.su", 'uses' => "{$controller}@switchUser"]);
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

        $entity = parent::updateSave($entity);

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