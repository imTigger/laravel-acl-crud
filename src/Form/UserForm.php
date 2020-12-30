<?php

namespace Imtigger\LaravelACLCRUD\Form;

use Kris\LaravelFormBuilder\Form;

class UserForm extends Form
{
    public function buildForm()
    {
        $this->method = $this->getMethod();
        $this->entity = $this->getModel();

        $this->add('name', 'text', [
            'label' => trans('laravel-acl-crud::ui.label.name'),
            'rules' => ['required', 'max:255']
        ]);

        $this->add('username', 'text', [
            'label' => trans('laravel-acl-crud::ui.label.username'),
            'rules' => ['required', 'max:255'],
        ]);
    
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
