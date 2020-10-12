<?php

namespace LaravelCode\EventSourcing\Contracts;

interface EventInterface extends BaseEventInterface
{
    public function getCommandId(): string;

    public function getAuthorId();
}
