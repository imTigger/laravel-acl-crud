<?php

namespace Imtigger\LaravelACLCRUD\Form;

use App\Models\Admin;
use App\Models\Permission;
use App\Models\Role;
use Imtigger\LaravelACLCRUD\AclHelper;
use Illuminate\Support\Facades\Auth;
use Kris\LaravelFormBuilder\Form;

class AdminForm extends Form
{
    public function buildForm()
    {
        $this->method = $this->getMethod();
        $this->entity = $this->getData('entity');

        $this->add('name', 'text', [
            'label' => trans('laravel-acl-crud::ui.label.name'),
            'rules' => ['required', 'max:255']
        ]);

        $this->add('username', 'text', [
            'label' => trans('laravel-acl-crud::ui.label.username'),
            'rules' => ['required', 'max:255', in_array($this->method, ['get', 'post']) ? 'unique:admins' : "unique:admins,username,{$this->entity->id}"]
        ]);

        $this->add('roles', 'entity', [
            'label' => trans('laravel-acl-crud::ui.label.roles'),
            'rules' => [],
            'class' => Role::class,
            'multiple' => true,
            'option_attributes' => Role::get()->reject(function ($role, $key) {
                return AclHelper::hasAllPermissionsOfRole(Auth::user()->allPermissions()->pluck('id'), $role);
            })->keyBy('id')->map(function ($role) { return ['disabled']; })->toArray(),
        ]);

        $this->add('permissions', 'entity', [
            'label' => trans('laravel-acl-crud::ui.label.additional_permissions'),
            'help_block' => [
                'text' => trans('laravel-acl-crud::ui.label.additional_permissions_help'),
                'tag' => 'p',
                'attr' => ['class' => 'text-muted']
            ],
            'rules' => [],
            'class' => Permission::class,
            'property' => 'display_name',
            'multiple' => true,
            'option_attributes' => Permission::whereNotIn('id', Auth::user()->allPermissions()->pluck('id'))->get()->keyBy('id')->map(function ($permission) { return ['disabled']; })->toArray()
        ]);

        // Don't show password on view/delete page
        if (!in_array($this->method, ['get', 'delete'])) {
            $this->add('password', 'password', [
                'label' => trans('laravel-acl-crud::ui.label.new_password'),
                'rules' => [$this->method == 'post' ? 'required' : 'nullable', 'min:6', 'confirmed'],
                'value' => ''
            ]);

            $this->add('password_confirmation', 'password', [
                'label' => trans('laravel-acl-crud::ui.label.repeat_new_password'),
                'rules' => [],
                'value' => ''
            ]);
        }
    }
}
