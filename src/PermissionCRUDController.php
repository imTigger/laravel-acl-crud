<?php
namespace Imtigger\LaravelACLCRUD;

use Imtigger\LaravelCRUD\CRUDController;

class PermissionCRUDController extends CRUDController
{
    protected $viewPrefix = 'admin.permission';
    protected $routePrefix = 'admin.permission';
    protected $entityName = 'Permission';
    protected $entityClass = \App\Models\Permission::class;
    protected $formClass = \App\Forms\PermissionForm::class;
}