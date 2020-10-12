<?php

namespace LaravelCode\EventSourcing\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class ESEvent extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:es:event';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new event for event sourced models';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Event';

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
        $apply = $this->option('apply');
        if ($apply) {
            return __DIR__.'/stubs/event-apply.stub';
        }

        return __DIR__.'/stubs/event.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        $apply = $this->option('apply');
        if ($apply) {
            return $rootNamespace.'\Events\Apply';
        }

        return $rootNamespace.'\Events';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['apply', null, InputOption::VALUE_NONE, 'Create as apply event'],
        ];
    }
}
