<?php

namespace LaravelCode\EventSourcing\Contracts;

interface ApplyEventInterface extends BaseEventInterface
{
    public function setStoreEvent(bool $event): void;

    public function isStoreEvent(): bool;

    public function getRevisionNumber(): int;

    public function setRevisionNumber(int $revisionNumber): void;

    public function getCreatedAt(): string;

    public function setCreatedAt(string $createdAt): void;

    public function getUpdatedAt(): string;

    public function setUpdatedAt(string $updatedAt): void;

}
