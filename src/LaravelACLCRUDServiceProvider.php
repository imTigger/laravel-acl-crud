<?php
namespace Imtigger\LaravelACLCRUD;

use Illuminate\Support\ServiceProvider;

class LaravelACLCRUDServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

    }

    public function boot()
    {
        $this->loadTranslationsFrom(__DIR__.'/resources/lang', 'laravel-acl-crud');
    }

}