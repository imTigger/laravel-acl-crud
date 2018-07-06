<?php

namespace Imtigger\LaravelACLCRUD;

use App\Forms\UserForm;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;
use Illuminate\Routing\Controller;
use Kris\LaravelFormBuilder\FormBuilderTrait;
use Input;
use DataTables;

class UserCRUDController extends Controller
{
    use FormBuilderTrait;

    protected $viewPrefix = 'admin.user';
    protected $routePrefix = 'admin.user';
    protected $entityName = 'backend.entity.user';
    protected $entityClass = User::class;
    protected $formClass = UserForm::class;
    protected $with = [];
    protected $rawColumns = ['actions'];

    protected $isCreatable = true;
    protected $isEditable = true;
    protected $isViewable = true;
    protected $isDeletable = true;

    protected $data = [];

    public function __construct() {
        $this->data['viewPrefix'] = $this->viewPrefix;
        $this->data['routePrefix'] = $this->routePrefix;
        $this->data['entityName'] = $this->entityName;

        $this->data['isCreatable'] = $this->isCreatable;
        $this->data['isEditable'] = $this->isEditable;
        $this->data['isViewable'] = $this->isViewable;
        $this->data['isDeletable'] = $this->isDeletable;
    }

    /**
     * @param $prefix
     * @param $controller
     * @param $as
     *
     * Shortcut for creating group of named route
     */
    public static function routes($prefix, $controller, $as) {
        $prefix_of_prefix = substr(strrev(strstr(strrev($as), '.', false)), 0, -1);
        \Route::resource("{$prefix}", "{$controller}", ['as' => $prefix_of_prefix]);
        \Route::get("{$prefix}/delete/{id}", ['as' => "{$as}.delete", 'uses' => "{$controller}@delete"]);
        \Route::get("{$prefix}/ajax/list", ['as' => "{$as}.ajax.list", 'uses' => "{$controller}@ajaxList", 'laroute' => true]);
    }

    /**
     * HTTP index handler
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        return view("{$this->viewPrefix}.index", $this->data);
    }

    /**
     * HTTP show handler
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id) {
        if (!$this->isViewable) {
            abort(404);
        }

        $entity = ($this->entityClass)::findOrFail($id);

        $form = $this->showForm($entity, $id);
        $form->disableFields();

        $this->data['entity'] = $entity;
        $this->data['form'] = $form;
        $this->data['action'] = 'show';

        return view("{$this->viewPrefix}.show", $this->data);
    }


    /**
     * Return LaravelFormBuilder Form used in show
     * Override this method to modify the form displayed in show
     *
     * @param Model $entity
     * @param int $id
     * @return \Kris\LaravelFormBuilder\Form
     */
    protected function showForm($entity, $id) {
        return $this->form($this->formClass, [
            'method' => 'get',
            'url' => route("$this->routePrefix.show", $id),
            'class' => 'simple-form',
            'model' => $entity
        ]);
    }

    /**
     * HTTP create handler
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        if (!$this->isCreatable) {
            abort(404);
        }

        $form = $this->createForm();
        $this->data['form'] = $form;
        return view("{$this->viewPrefix}.create", $this->data);
    }

    /**
     * Return LaravelFormBuilder Form used in create
     *
     * @return \Kris\LaravelFormBuilder\Form
     */
    protected function createForm() {
        $form = $this->form($this->formClass, [
            'method' => 'post',
            'url' => route("$this->routePrefix.store"),
            'class' => 'simple-form'
        ]);

        return $form;
    }

    /**
     * HTTP store handler
     *
     * @return \Illuminate\Http\Response
     */
    public function store() {
        dd($this->isCreatable);
        if (!$this->isCreatable) {
            abort(404);
        }

        $form = $this->storeForm();

        if (!$form->isValid()) {
            return redirect()->back()->withErrors($form->getErrors())->withInput();
        }

        $this->storeSave();

        return redirect()->route("$this->routePrefix.index")->with('status', trans('backend.message.create_success', ['name' => trans($this->entityName)]));
    }

    /**
     * Return LaravelFormBuilder Form used in store validation
     *
     * @return \Kris\LaravelFormBuilder\Form
     */
    protected function storeForm() {
        $form = $this->form($this->formClass, [
            'method' => 'post'
        ]);

        return $form;
    }

    /**
     * Trigger when store method
     * Override this method to add additional operations
     *
     * @return Model
     */
    protected function storeSave() {
        $entity = new $this->entityClass;

        $entity->name = Input::get('name');
        $entity->email = Input::get('email');
        $entity->password = bcrypt(Input::get('password'));

        $entity->save();

        return $entity;
    }

    /**
     * HTTP edit handler
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        if (!$this->isEditable) {
            abort(404);
        }

        $entity = ($this->entityClass)::findOrFail($id);

        $form = $this->editForm($entity);

        $this->data['entity'] = $entity;
        $this->data['form'] = $form;
        $this->data['action'] = 'edit';

        return view("{$this->viewPrefix}.edit", $this->data);
    }

    /**
     * Return LaravelFormBuilder Form used in edit
     *
     * @param Model $entity
     * @return \Kris\LaravelFormBuilder\Form
     */
    protected function editForm($entity) {
        $form = $this->form($this->formClass, [
            'method' => 'patch',
            'url' => route("$this->routePrefix.update", $entity->id),
            'class' => 'simple-form',
            'model' => $entity,
            'autocomplete' => 'off'
        ], ['entity' => $entity]);

        return $form;
    }

    /**
     * HTTP update handler
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function update($id) {
        if (!$this->isEditable) {
            abort(404);
        }

        $entity = ($this->entityClass)::findOrFail($id);

        $form = $this->updateForm($entity);

        if (!$form->isValid()) {
            return redirect()->back()->withErrors($form->getErrors())->withInput();
        }

        $this->updateSave($entity);

        return redirect()->route("$this->routePrefix.index")->with('status', trans('backend.message.edit_success', ['name' => trans($this->entityName)]));
    }

    /**
     * Return LaravelFormBuilder Form used in update
     * @param $entity
     * @return \Kris\LaravelFormBuilder\Form
     */
    protected function updateForm($entity) {
        $form = $this->form($this->formClass, [
            'method' => 'patch'
        ], ['entity' => $entity]);

        return $form;
    }

    /**
     * Trigger when update method
     * Override this method to add additinal operations
     *
     * @param Model $entity
     * @return Model $entity
     */
    protected function updateSave($entity) {
        $entity->name = Input::get('name');
        $entity->email = Input::get('email');

        if (!empty(Input::get('password'))) {
            $entity->password = bcrypt(Input::get('password'));
        }

        $entity->save();

        return $entity;
    }



    /**
     * HTTP delete handler
     *
     * @param int $id
     * @return mixed
     */
    public function delete($id) {
        if (!$this->isDeletable) {
            abort(404);
        }

        $entity = ($this->entityClass)::findOrFail($id);

        $form = $this->deleteForm($entity, $id);
        $form->disableFields();

        $this->data['entity'] = $entity;
        $this->data['form'] = $form;
        $this->data['action'] = 'show';

        return view("{$this->viewPrefix}.delete", $this->data);
    }

    /**
     * Return LaravelFormBuilder Form used in delete
     * Override this method to modify the form displayed in delete
     *
     * @param Model $entity
     * @param int $id
     * @return \Kris\LaravelFormBuilder\Form
     */
    protected function deleteForm($entity, $id) {
        return $this->form($this->formClass, [
            'method' => 'delete',
            'model' => $entity,
            'url' => route("$this->routePrefix.destroy", $id)
        ], ['entity' => $entity]);
    }

    /**
     * HTTP destroy handler
     *
     * @param $id
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function destroy($id) {
        if (!$this->isDeletable) {
            abort(404);
        }

        $entity = ($this->entityClass)::findOrFail($id);

        $this->destroySave($entity);

        return redirect()->route("$this->routePrefix.index")->with('status', trans('backend.message.delete_success', ['name' => trans($this->entityName)]));
    }

    /**
     * Trigger when destroy method
     * Override this method to add additinal operations
     *
     * @param Model $entity
     * @throws \Exception
     */
    protected function destroySave($entity) {
        $entity->delete();
    }

    /**
     * HTTP ajax.list query builder
     *
     * @return \Eloquent
     */
    protected function ajaxListQuery() {
        $builder = ($this->entityClass)::query();

        // Add 'with' relations
        if (is_array($this->with) && !empty($this->with)) {
            foreach ($this->with as $relation) {
                $builder->with($relation);
            }
        }

        return $builder;
    }



    /**
     * Extra Datatable action field, append string after default actions
     *
     * @param $item
     * @return string
     */
    protected function ajaxListActions($item)
    {
        return
            ($this->isViewable ? '<a href="' . route("{$this->routePrefix}.show", [$item->id]) .'" class="btn btn-xs btn-success"><i class="glyphicon glyphicon-eye-open"></i> ' . trans('laravel-crud::ui.button.view') . '</a> ' : '') .
            ($this->isEditable ? '<a href="' . route("{$this->routePrefix}.edit", [$item->id]) .'" class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-edit"></i> ' . trans('laravel-crud::ui.button.edit') . '</a> ' : '') .
            ($this->isDeletable ? '<a href="' . route("{$this->routePrefix}.delete", [$item->id]) .'" class="btn btn-xs btn-danger"><i class="glyphicon glyphicon-trash"></i> ' . trans('laravel-crud::ui.button.delete') . '</a> ' : '');
    }

    /**
     * HTTP ajax.list handler
     *
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function ajaxListDataTable($items) {
        $datatable = DataTables::of($items)
            ->addColumn('actions', function ($item) {
                return $this->ajaxListActions($item);
            });

        // Set rawColumns
        if (!empty($this->rawColumns) && method_exists($datatable, 'rawColumns')) {
            $datatable->rawColumns($this->rawColumns);
        }

        return $datatable;
    }

    /**
     * HTTP ajax.list handler
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function ajaxList() {
        $items = $this->ajaxListQuery();

        return $this->ajaxListDataTable($items)->make(true);
    }
}