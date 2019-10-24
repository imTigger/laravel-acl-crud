<?php

namespace Imtigger\LaravelACLCRUD\Form;

use Kris\LaravelFormBuilder\Form;

class PermissionForm extends Form
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
    }
}
