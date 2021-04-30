<?php

namespace LaravelCode\EventSourcing;

use LaravelCode\EventSourcing\Console\EsChain;
use LaravelCode\EventSourcing\Console\EsCommand;
use LaravelCode\EventSourcing\Console\ESEvent;
use LaravelCode\EventSourcing\Console\ESListener;
use LaravelCode\EventSourcing\Console\EventReplay;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/database/migrations');

            $this->commands([
                ESEvent::class,
                ESListener::class,
                EsCommand::class,
                EsChain::class,
                EventReplay::class,
            ]);
        }
    }

    public function register(): void
    {
    }
}
