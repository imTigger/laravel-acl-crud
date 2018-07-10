<?php

namespace Imtigger\LaravelACLCRUD;

use App\Forms\UserForm;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Routing\Controller;
use Imtigger\LaravelCRUD\CRUDController;
use Kris\LaravelFormBuilder\FormBuilderTrait;
use Input;
use DataTables;

class UserCRUDController extends CRUDController
{
    use FormBuilderTrait;

    protected $viewPrefix = 'admin.user';
    protected $routePrefix = 'admin.user';
    protected $entityName = 'backend.entity.user';
    protected $entityClass = User::class;
    protected $formClass = UserForm::class;
    protected $permissionPrefix = 'user';
    protected $with = [];
    protected $rawColumns = ['actions'];

    protected $isDeletable = true;



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
}