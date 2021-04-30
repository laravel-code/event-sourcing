<?php

namespace LaravelCode\EventSourcing\Exceptions;

class InputListenerValidationException extends \Exception
{
    protected $message = 'The listener class must end with Listener, e.g. CreateListener';

}
