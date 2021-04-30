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
     * @param string $rawName
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
     * @param string $name
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);

        $stub = $this->handleCommand($stub);
        $stub = $this->handleEvent($stub);

        return $stub;
    }

    private function handleCommand($stub)
    {
        $command = $this->option('command');

        if (empty($command)) {
            return $stub;
        }

        if (! Str::startsWith($command, [
            $this->laravel->getNamespace(),
            'Illuminate',
            '\\',
        ])) {
            $command = $this->laravel->getNamespace().'Commands\\'.$command;
        }

        $command = $this->qualifyClass($command);

        $stub = str_replace(
            'DummyEvent', class_basename($command), $stub
        );

        return str_replace(
            'DummyFullEvent', trim($command, '\\'), $stub
        );
    }

    private function handleEvent($stub)
    {
        $event = $this->option('event');

        if (empty($event)) {
            return $stub;
        }

        if (! Str::startsWith($event, [
            $this->laravel->getNamespace(),
            'Illuminate',
            '\\',
        ])) {
            $event = $this->laravel->getNamespace().'Models\\Events\\'.$event;
        }

        $event = $this->qualifyClass($event);

        $stub = str_replace(
            'DummyEvent', class_basename($event), $stub
        );

        return str_replace(
            'DummyFullEvent', trim($event, '\\'), $stub
        );
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Listeners';
    }

    protected function getArguments()
    {
        return parent::getArguments() +
            [
                ['command', InputOption::VALUE_REQUIRED, 'The command class being listened for'],

                ['event', InputOption::VALUE_REQUIRED, 'The event class being listened for'],
            ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['queued', null, InputOption::VALUE_NONE, 'Indicates the event listener should be queued'],
        ];
    }
}
