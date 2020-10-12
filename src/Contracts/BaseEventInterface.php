<?php

namespace LaravelCode\EventSourcing\Contracts;

interface BaseEventInterface
{
    public function setId($id): void;

    public function getId();

    public function setCommandId(string $id): void;

    public function getCommandId(): string;

    public function setAuthorId($id): void;

    public function getAuthorId();
}
