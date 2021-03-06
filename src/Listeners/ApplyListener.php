<?php

namespace LaravelCode\EventSourcing\Listeners;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use LaravelCode\EventSourcing\Contracts\ApplyEventInterface;
use LaravelCode\EventSourcing\Contracts\BaseEventInterface;
use LaravelCode\EventSourcing\Contracts\EventInterface;
use LaravelCode\EventSourcing\Models\Command;
use LaravelCode\EventSourcing\Models\CommandError;
use LaravelCode\EventSourcing\Models\Event;
use LaravelCode\EventSourcing\PrimaryKey;
use ReflectionClass;
use ReflectionMethod;
use Str;

/**
 * Trait ApplyListener.
 *
 * @property Model $entity
 */
trait ApplyListener
{
    public ?Model $entity = null;

    private int $revisionNumber = 0;

    private EventInterface $command;

    private bool $isDeleted = false;

    private string $_model;

    /**
     * @throws Exception
     */
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

            if ($this->entity->push()) {
                $this->storeEvent($event);
            }
        } catch (Exception $exception) {
            $this->logException($event->getCommandId(), $exception->getMessage());

            throw $exception;
        }
    }

    private function storeEvent(ApplyEventInterface $event): void
    {
        if (! $event->isStoreEvent()) {
            return;
        }

        (new Event([
            'id' => $event->getEventId(),
            'command_id' => $event->getCommandId(),
            'model' => $this->getModel(),
            'resource_id' => $this->entity->id,
            'payload' => json_encode($event->toPayload()),
            'revision_number' => $this->revisionNumber,
            'key' => 'string',
            'author_id' => $event->getAuthorId(),
            'class' => get_class($event),
        ]))->save();
    }

    /**
     * @throws Exception
     */
    private function loadEntity(BaseEventInterface $event)
    {
        $modelClass = $this->getModel();
        $this->entity = (new $modelClass);
        $this->entity->revision_number = 1;
        $this->revisionNumber = 1;

        if ($event->getId() === PrimaryKey::UUID) {
            $event->setId(Str::uuid());
            $this->entity->id = $event->getId();

            return;
        }

        if ($event->getId() === PrimaryKey::AUTO_INCREMENT) {
            return;
        }

        if ($event->getId() === PrimaryKey::CALCULATE) {
            throw new Exception('Not implemented');
        }

        if ($event->getId()) {
            if ($event instanceof EventInterface) {
                $this->entity = $this->entity->findOrNew($event->getId());
                $this->entity->revision_number = $this->entity->revision_number + 1;
                $this->validateRevisionNumber();

                return;
            }

            if ($event instanceof ApplyEventInterface) {
                if ($event->isStoreEvent()) {
                    try {
                        $entity = $this->entity->findOrFail($event->getId());
                        $this->entity = $entity;
                    } catch (ModelNotFoundException $exception) {
                        $modelClass = $this->getModel();
                        $this->entity = (new $modelClass);
                        $this->entity->id = $event->getId();
                    }

                    $this->entity->revision_number = $this->entity->revision_number + 1;
                    $this->validateRevisionNumber();

                    return;
                }
                /**
                 * When we replay events we do not store the events
                 * We will also not check if the revision_number is valid.
                 *
                 * @var Model $entity
                 */
                $entity = $this->entity->findOrNew($event->getId());
                $this->entity = $entity;
                $this->entity->id = $event->getId();
                $this->entity->revision_number = $event->getRevisionNumber();
                if ($this->entity->timestamps === true) {
                    if (empty($this->entity->created_at)) {
                        $this->entity->created_at = $event->getCreatedAt();
                    }
                    $this->entity->updated_at = $event->getUpdatedAt();
                }
            }
        }
    }

    private function validateRevisionNumber()
    {
        if ($this->entity->id === null) {
            return;
        }
        $revisionNumber = Event::where('model', $this->getModel())
            ->where('resource_id', $this->entity->id)
            ->max('revision_number');

        $this->revisionNumber = $revisionNumber + 1;
        if ($this->revisionNumber !== $this->entity->revision_number) {
            throw new Exception(sprintf('Incorrect revision_number expected %s but got %s', $this->revisionNumber, $this->entity->revision_number));
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
     * @throws Exception
     */
    private function extractApplyFunction(string $class)
    {
        $list = explode('\\', $class);
        $closure = 'apply'.end($list);

        if (! is_callable([get_called_class(), $closure])) {
            throw new Exception($closure.' is not set in '.get_called_class());
        }

        return $closure;
    }

    public function handle(EventInterface $event): void
    {
        try {
            $commandClass = $this->getEventName(get_called_class());
            $class = $this->getClassName(get_class($event));
            if ($class !== $commandClass) {
                return;
            }
            $this->command = $event;
            $this->loadEntity($event);
            $this->handleCommand($event);

            $this->updateCommand($event->getCommandId(), Command::STATUS_HANDLED);
        } catch (Exception $exception) {
            $this->logException($event->getCommandId(), $exception->getMessage().$exception->getTraceAsString());
            throw $exception;
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

    public function event(ApplyEventInterface $event)
    {
        $event->setCommandId($this->command->getCommandId());
        $event->setAuthorId($this->command->getAuthorId());
        event($event);
    }

    private function getEventName(string $class): string
    {
        if (preg_match('/^(.*)Listener$/', $class, $matches)) {
            return $this->getClassName($matches[1]);
        }

        return $this->getClassName($class);
    }

    private function getClassName(string $class): string
    {
        $list = explode('\\', $class);

        return end($list);
    }

    /**
     * @throws Exception
     */
    private function getModel()
    {
        if (isset($this->model)) {
            return $this->model;
        }

        if (! empty($this->_model)) {
            return $this->_model;
        }

        $calledClass = get_called_class();
        $class = str_replace('\\Listeners\\', '\\Models\\', $calledClass);

        $list = explode('\\', $class);
        array_pop($list);

        $modelClass = implode('\\', $list);
        if (class_exists($modelClass)) {
            $this->_model = $modelClass;

            return $this->_model;
        }

        throw new Exception('Please set the public property $model in the listener');
    }

    public function delete()
    {
        $this->entity->delete();
        $this->isDeleted = true;
    }
}
