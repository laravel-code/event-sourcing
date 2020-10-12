<?php

namespace LaravelCode\EventSourcing\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class ESListener extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:es:listener';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new listener for event sourced models';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Listener';

    /**
     * Determine if the class already exists.
     *
     * @param  string  $rawName
     * @return bool
     */
    protected function alreadyExists($rawName)
    {
        return class_exists($rawName) ||
            $this->files->exists($this->getPath($this->qualifyClass($rawName)));
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/listener.stub';
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $event = $this->option('event');

        if (! Str::startsWith($event, [
            $this->laravel->getNamespace(),
            'Illuminate',
            '\\',
        ])) {
            $event = $this->laravel->getNamespace().'Events\\'.$event;
        }

        $stub = str_replace(
            'DummyEvent', class_basename($event), parent::buildClass($name)
        );

        return str_replace(
            'DummyFullEvent', trim($event, '\\'), $stub
        );
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Listeners';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['event', 'e', InputOption::VALUE_OPTIONAL, 'The event class being listened for'],

            ['queued', null, InputOption::VALUE_NONE, 'Indicates the event listener should be queued'],
        ];
    }
}
