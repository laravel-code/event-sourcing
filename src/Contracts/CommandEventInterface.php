<?php

namespace LaravelCode\EventSourcing\Contracts;

interface CommandEventInterface extends EventInterface
{
    public function getCommandId(): string;

    public function getAuthorId();
}
