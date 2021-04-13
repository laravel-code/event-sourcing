<?php

namespace LaravelCode\EventSourcing\Event;

use Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use LaravelCode\EventSourcing\Models\Command;
use LaravelCode\EventSourcing\Models\Event;
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

    public static function handleEvent($id = null, array $params = [], array $options = [])
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
        $response = event($class);
        $appendResponse = static::handleOptionsResponse($options, $commandId);

        return ['command_id' => $commandId] + $appendResponse;
    }

    private static function handleOptionsResponse(array $options, $commandId): array
    {
        if (! $options['response'] ?? null) {
            return [];
        }

        $data = [];

        $data['command'] = Command::findOrFail($commandId)->toArray();
        if ($data['command']['status'] !== Command::STATUS_HANDLED) {
            return [];
        }

        $data['event'] = Event::where('command_id', '=', $commandId)->firstOrFail()->toArray();

        $relations = array_filter($options['response']['entity'] ?? [], function ($value, $key) {
            if (is_int($key) && strpos($value, '.')) {
                return true;
            }

            return false;
        }, ARRAY_FILTER_USE_BOTH);

        $relations = array_map(function ($relation) {
            $list = explode('.', $relation);
            array_pop($list);

            if (count($list) <= 1) {
                return implode('.', $list);
            }

            $list = array_filter($list, function ($value) {
                return $value !== '*';
            });

            return implode('.', $list);
        }, $relations);

        $data['entity'] = (new $data['event']['model'])
            ->with($relations)
            ->find($data['event']['resource_id'])->toArray();

        $response = [];
        foreach ($options['response'] as $key => $value) {
            if (is_int($key) && isset($data[$value])) {
                $response = $response + $data[$value];

                continue;
            }

            if (is_string($key) && isset($data[$key])) {
                if (is_string($value)) {
                    $value = [$value];
                }

                $entity = $data[$key];

                foreach ($value as $column) {
                    $response[static::getColumnName($column)] = data_get($entity, $column, null);
                }
            }
        }

        return $response;
    }

    private static function getColumnName($value)
    {
        if (strpos($value, '*') === false) {
            return $value;
        }

        $list = explode('.', $value);

        return array_pop($list);
    }
}
