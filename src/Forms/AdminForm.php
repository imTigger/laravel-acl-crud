<?php

namespace Imtigger\LaravelACLCRUD\Form;

use App\Models\Admin;
use App\Models\Permission;
use App\Models\Role;
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
            'query_builder' => function ($query) {
                // Hide roles that user don't have
                $hiddenRoles = collect(Admin::$protectedRoles)->diff(Auth::user()->roles->pluck('name'));
                return $query->whereNotIn('name', $hiddenRoles);
            }
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
            'query_builder' => function ($query) {
                // User can only select it's own permissions
                return $query->whereIn('name', Auth::user()->allPermissions()->pluck('name'));
            },
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
