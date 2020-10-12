<?php

namespace LaravelCode\EventSourcing\Listeners;

use Illuminate\Database\Eloquent\Model;
use LaravelCode\EventSourcing\Contracts\ApplyEventInterface;
use LaravelCode\EventSourcing\Contracts\EventInterface;
use LaravelCode\EventSourcing\Exceptions\ModelNotFoundException;
use LaravelCode\EventSourcing\Models\Command;
use LaravelCode\EventSourcing\Models\CommandError;
use LaravelCode\EventSourcing\Models\Event;
use ReflectionClass;
use ReflectionMethod;
use Str;

/**
 * Trait ApplyListener.
 */
trait ApplyListener
{
    public ?Model $entity = null;

    private int $revisionNumber = 0;

    private EventInterface $command;

    private bool $isDeleted = false;

    public function __invoke(ApplyEventInterface $event)
    {
        try {
            $this->loadEntity($event);

            $closure = $this->extractApplyFunction(get_class($event));
            call_user_func([$this, $closure], $event);

            if ($this->isDeleted) {
                $this->storeEvent($event);

                return;
            }

            if ($this->entity->save()) {
                $this->storeEvent($event);
            }
        } catch (\Exception $exception) {
            $this->logException($event->getCommandId(), $exception->getMessage());
        }
    }

    private function storeEvent($event)
    {
        (new Event([
            'id' => $event->getEventId(),
            'command_id' => $event->getCommandId(),
            'model' => $this->model,
            'resource_id' => $this->entity->id,
            'payload' => json_encode($event->toPayload()),
            'revision_number' => $this->revisionNumber,
            'key' => 'string',
            'author_id' => $event->getAuthorId(),
            'class' => get_class($event),
        ]))->save();
    }

    private function loadEntity(EventInterface $event)
    {
        if ($event->getId()) {
            /** @var Model $model */
            $model = (new $this->model);
            $this->entity = $model->find($event->getId());
            if (! $this->entity) {
                throw new ModelNotFoundException();
            }
            $this->entity->revision_number = $this->entity->revision_number + 1;
            $this->validateRevisionNumber();

            return;
        }
        $this->entity = (new $this->model);
        $this->entity->revision_number = 1;
        $this->revisionNumber = 1;
    }

    private function validateRevisionNumber()
    {
        if ($this->entity->id === null) {
            return;
        }

        $revisionNumber = Event::where('model', $this->model)
            ->where('resource_id', $this->entity->id)
            ->max('revision_number');
        $this->revisionNumber = $revisionNumber + 1;
        if ($this->revisionNumber !== $this->entity->revision_number) {
            throw new \Exception('Incorrect revision_number');
        }
    }

    /**
     * Subscribe to all apply* methods.
     *
     * @param $events
     * @throws \ReflectionException
     */
    public function subscribe($events)
    {
        $class = new ReflectionClass(get_called_class());
        $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            preg_match('/^apply(.*)$/i', $method->name, $matches);
            if ($matches) {
                $events->listen(
                    $method->getParameters()[0]->getType()->getName(), // TODO this will most likely break
                    [get_called_class(), '__'.$method->name]
                );
            }

            if ($method->name === 'handleCommand') {
                $events->listen(
                    $method->getParameters()[0]->getType()->getName(), // TODO this will most likely break
                    [get_called_class(), 'handle']
                );
            }
        }
    }

    /**
     * @param string $class
     * @return string
     * @throws \Exception
     */
    private function extractApplyFunction(string $class)
    {
        $list = explode('\\', $class);
        $closure = 'apply'.end($list);

        if (! is_callable([get_called_class(), $closure])) {
            throw new \Exception($closure.' is not set in '.get_called_class());
        }

        return $closure;
    }

    public function handle(EventInterface $event)
    {
        try {
            $commandClass = $this->getCommandName(get_called_class());
            $class = $this->getClassName(get_class($event));

            if ($class !== $commandClass) {
                return false;
            }

            $this->command = $event;
            $this->loadEntity($event);
            $this->handleCommand($event);
            $this->updateCommand($event->getCommandId(), Command::STATUS_HANDLED);
        } catch (\Exception $exception) {
            $this->logException($event->getCommandId(), $exception->getMessage());
        }
    }

    private function logException($commandId, $message)
    {
        (new CommandError([
            'id' => Str::uuid(),
            'command_id' => $commandId,
            'class' => get_called_class(),
            'message' => $message,
        ]))->save();

        $this->updateCommand($commandId, Command::STATUS_FAILED);
    }

    private function updateCommand($commandId, $status)
    {
        $command = Command::where('id', $commandId)->firstOrFail();
        if ($command->status === Command::STATUS_FAILED) {
            return;
        }

        $command->status = $status;
        $command->save();
    }

    public function event(EventInterface $event)
    {
        $event->setCommandId($this->command->getCommandId());
        $event->setAuthorId($this->command->getAuthorId());
        event($event);
    }

    private function getCommandName(string $class): string
    {
        return $this->getClassName(substr($class, 0, -8));
    }

    private function getClassName(string $class): string
    {
        $list = explode('\\', $class);

        return end($list);
    }

    public function delete()
    {
        $this->entity->delete();
        $this->isDeleted = true;
    }
}
