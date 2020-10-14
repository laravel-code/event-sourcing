<?php

namespace LaravelCode\EventSourcing\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use LaravelCode\EventSourcing\Contracts\ApplyEventInterface;
use LaravelCode\EventSourcing\Models\Event;

class EventReplay extends Command
{
    private int $count = 0;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'event:replay
     {--model= : Replay commands from Model}
     {--resource-id= : Replay commands from Model with an ID}
     {--command-id= : Replay a commandId}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Replay events';

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
     * @return int
     */
    public function handle()
    {
        if ($this->option('resource-id') && ! $this->option('model')) {
            $this->error('resource-id must be used in conjunction with --model');
        }

        Event::orderBy('created_at')
            ->where(function (Builder $query) {
                if ($this->option('model')) {
                    $query->where('model', $this->option('model'));
                }

                if ($this->option('resource-id')) {
                    $query->where('resource_id', $this->option('resource-id'));
                }

                if ($this->option('command-id')) {
                    $query->where('command_id', $this->option('command-id'));
                }
            })
            ->chunk(200, function ($events) {
                /** @var Event $event */
                foreach ($events as $event) {
                    $this->count++;
                    try {
                        if (class_exists($event->class)) {
                            $payload = collect(json_decode($event->payload));

                            /** @var ApplyEventInterface $replayEvent */
                            $replayEvent = call_user_func([$event->class, 'fromPayload'], $event->resource_id, $payload);
                            $replayEvent->setStoreEvent(false);
                            $replayEvent->setCommandId($event->command_id);
                            $replayEvent->setAuthorId($event->author_id);
                            $replayEvent->setRevisionNumber($event->revision_number);
                            $replayEvent->setCreatedAt($event->created_at);
                            $replayEvent->setUpdatedAt($event->updated_at);
                            event($replayEvent);
                        }
                    } catch (\Exception $exception) {
                        $this->error($exception->getMessage());
                        $this->line($exception->getTraceAsString());
                    }
                }
            });

        $this->line(sprintf('Replayed Events: %s', $this->count));
    }
}
