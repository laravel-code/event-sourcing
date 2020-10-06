<?php

namespace LaravelCode\EventSourcing;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use LaravelCode\Crud\Commands\CrudControllers;
use LaravelCode\Crud\Commands\CrudEvents;
use LaravelCode\Crud\Commands\CrudGenerate;
use LaravelCode\Crud\Commands\CrudRoutes;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        }
    }

    public function register(): void
    {
    }
}
