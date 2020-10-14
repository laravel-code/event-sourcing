<?php

namespace LaravelCode\EventSourcing\Contracts;

interface ApplyEventInterface extends BaseEventInterface
{
    public function setStoreEvent(bool $event): void;

    public function isStoreEvent(): bool;

    public function getRevisionNumber(): int;

    public function setRevisionNumber(int $revisionNumber): void;
}
