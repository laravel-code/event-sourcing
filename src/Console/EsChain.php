<?php

namespace LaravelCode\EventSourcing\Console;

use _HumbugBoxe5640220fe34\Nette\Neon\Exception;
use Illuminate\Console\Command;
use LaravelCode\EventSourcing\Exceptions\InputListenerValidationException;

class EsChain extends Command {


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:es:chain';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Command, Event and listener all at once';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $command = $this->ask('Command class within the namespace App\\Commands');
        $event = $this->ask('Event class within the namespace App\\Models\\Events');
        $listener = $this->askListener();

        \Artisan::call('make:es:command', [
            'name' => $command
        ]);

        \Artisan::call('make:es:event', [
            'name' => $event
        ]);

        \Artisan::call('make:es:listener', [
            'name' => $listener,
            'commandClass' => $command,
            'event' => $event,
        ]);

    }


    private function askListener()
    {
        try {
            $listener = $this->ask('Listener class within the namespace App\\Listener');
            if (!\Str::endsWith($listener, 'Listener')) {
                throw new InputListenerValidationException();
            }

            return $listener;
        } catch(InputListenerValidationException $exception) {
            $this->alert($exception->getMessage());
            return $this->askListener();
        }
    }
}
