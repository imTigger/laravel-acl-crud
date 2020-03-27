<?php

namespace Imtigger\LaravelACLCRUD\Form;

use App\Models\Permission;
use Kris\LaravelFormBuilder\Form;
use Illuminate\Support\Facades\Auth;

class RoleForm extends Form
{
    public function buildForm()
    {
        $this->method = $this->getMethod();
        $this->entity = $this->getModel();

        $this->add('name', 'text', [
            'label' => trans('laravel-acl-crud::ui.label.name'),
            'attr' => array_merge([], !empty($this->entity) ? ['readonly' => 'readonly'] : []),
            'rules' => ['required', 'max:255']
        ]);

        $this->add('display_name', 'text', [
            'label' => trans('laravel-acl-crud::ui.label.display_name'),
            'rules' => ['required', 'max:255']
        ]);

        $this->add('description', 'text', [
            'label' => trans('laravel-acl-crud::ui.label.description'),
            'rules' => ['required', 'max:255']
        ]);

        $this->add('permissions', 'entity', [
            'label' => trans('laravel-acl-crud::ui.label.permissions'),
            'class' => Permission::class,
            'property' => 'display_name',
            'expanded' => false,
            'multiple' => true,
            'rules' => [],
            'option_attributes' => Auth::user()->hasRole('root') ? [] : Permission::whereNotIn('id', Auth::user()->allPermissions()->pluck('id'))->get()->keyBy('id')->map(function ($permission) { return ['disabled']; })->toArray()
        ]);
    }
}
