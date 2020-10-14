<?php

namespace LaravelCode\EventSourcing\Event\Apply;

use Illuminate\Support\Collection;
use LaravelCode\EventSourcing\Contracts\ApplyEventInterface;
use Str;

abstract class ApplyEvent implements ApplyEventInterface
{
    protected ?string $id;
    protected string $commandId;
    protected string $eventId;
    protected $authorId;
    protected bool $storeEvent = true;
    protected string $createdAt;
    protected string $updatedAt;
    protected int $revisionNumber;

    public function __construct(string $id = null)
    {
        $this->id = $id;
        $this->eventId = Str::uuid();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getCommandId(): string
    {
        return $this->commandId;
    }

    /**
     * @param string $commandId
     */
    public function setCommandId(string $commandId): void
    {
        $this->commandId = $commandId;
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    /**
     * @return mixed
     */
    public function getAuthorId()
    {
        return $this->authorId;
    }

    /**
     * @param mixed $authorId
     */
    public function setAuthorId($authorId): void
    {
        $this->authorId = $authorId;
    }

    public static function fromPayload($id, Collection $collection)
    {
        $class = get_called_class();

        return new $class($id);
    }

    public function toPayload(): array
    {
        return [
            'id' => $this->getId(),
        ];
    }

    public function setStoreEvent(bool $store): void
    {
        $this->storeEvent = $store;
    }

    /**
     * @return bool
     */
    public function isStoreEvent(): bool
    {
        return $this->storeEvent;
    }

    /**
     * @return int
     */
    public function getRevisionNumber(): int
    {
        return $this->revisionNumber;
    }

    /**
     * @param int $revisionNumber
     */
    public function setRevisionNumber(int $revisionNumber): void
    {
        $this->revisionNumber = $revisionNumber;
    }

    /**
     * @return string
     */
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * @param string $createdAt
     */
    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return string
     */
    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    /**
     * @param string $updatedAt
     */
    public function setUpdatedAt(string $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
