<?php

namespace LaravelCode\EventSourcing\Event;

use Illuminate\Support\Collection;

trait DataInitiatorTrait
{
    private array $_data = [];

    /**
     * Set this property in the class to enable strict mode.
     *
     * This will ensure that the requested param was set,
     * if not then it will throw an exception
     *
     * @var bool
     */
    // protected bool $strict = true;

    /**
     * @param array $values
     */
    private function constructData(array $values)
    {
        try {
            $ref = new \ReflectionClass(static::class);
            $params = $ref->getMethod('__construct')->getParameters();
            foreach ($params as $param) {
                if (0 === $param->getPosition() && 'id' === $param->getName()) {
                    if (is_callable([$this, 'setId'])) {
                        $this->setId($values[0]);
                    }
                }

                $this->_data[$param->getName()] = $values[$param->getPosition()];
            }
        } catch (\Exception $exception) {
            \Log::alert($exception->getMessage());
        }
    }

    /**
     * @param string $name
     * @return mixed|null
     * @throws \Exception
     */
    public function __get(string $name)
    {
        if (! isset($this->strict)) {
            return $this->_data[$name] ?? null;
        }

        if (! array_key_exists($name, $this->_data)) {
            throw new \Exception(sprintf('You cannot access an attribute [%s] before it is set.', $name));
        }

        return $this->_data[$name];
    }

    /**
     * @throws \ReflectionException
     */
    public static function fromPayload($id, Collection $collection): self
    {
        $ref = new \ReflectionClass(static::class);
        $params = $ref->getMethod('__construct')->getParameters();
        $rest = [];

        foreach ($params as $param) {
            if ('id' === $param->getName() && 0 === $param->getPosition()) {
                $rest[] = $id;

                continue;
            }

            $name = \Str::snake($param->getName());
            $value = $collection->get($name, $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null);

            $rest[] = $value;
        }

        return new self(...$rest);
    }

    /**
     * @throws \ReflectionException
     */
    public function toPayload(): array
    {
        $ref = new \ReflectionClass(static::class);
        $params = $ref->getMethod('__construct')->getParameters();

        $ret = [];
        foreach ($params as $param) {
            if (0 === $param->getPosition()) {
                $ret['id'] = $this->getId();
            }

            $ret[\Str::snake($param->getName())] = $this->{$param->getName()};
        }

        return $ret;
    }
}
