<?php

namespace LaravelCode\EventSourcing;


use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use LaravelCode\EventSourcing\Console\ESEvent;
use LaravelCode\EventSourcing\Console\ESListener;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/database/migrations');

            $this->commands([
                ESEvent::class,
                ESListener::class,
            ]);
        }
    }

    public function register(): void
    {
    }
}
