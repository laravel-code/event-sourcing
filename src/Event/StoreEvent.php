<?php

namespace LaravelCode\EventSourcing\EventSourcing;

use Auth;
use Illuminate\Support\Collection;
use LaravelCode\EventSourcing\Models\Command;
use Str;

trait StoreEvent
{
    /**
     * @var string|int|null
     */
    public $authorId;
    /**
     * @var string|int|null
     */
    private $id;

    private string $commandId;

    /**
     * @return string|int|null
     */
    public function getAuthorId()
    {
        return $this->authorId;
    }

    /**
     * @param int|string|null $authorId
     */
    public function setAuthorId($authorId): void
    {
        $this->authorId = $authorId;
    }

    /**
     * @return int|string|null
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getCommandId(): string
    {
        return $this->commandId;
    }

    public function setCommandId(string $id): void
    {
        $this->commandId = $id;
    }

    abstract public static function fromPayload($id, Collection $collection);

    abstract public function toPayload(): array;

    public static function handleEvent($id = null, array $params = [])
    {
        $commandId = Str::uuid();

        /** @var StoreEvent $class */
        $class = call_user_func([get_called_class(), 'fromPayload'], $id, new Collection($params));
        $class->setCommandId($commandId);
        $class->setAuthorId(Auth::id());

        $command = new Command([
            'id' => $commandId,
            'class' => get_class($class),
            'payload' => json_encode($class->toPayload()),
            'status' => Command::STATUS_RECEIVED,
            'author_id' => Auth::id(),
            'key' => 'encryption_key',
        ]);

        $command->save();
        event($class);

        return ['command_id' => $commandId];
    }
}
