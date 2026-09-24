<?php

namespace App\Exceptions;

use Exception;

class TagActivationException extends Exception
{
    public function __construct(string $message, public readonly string $errorCode)
    {
        parent::__construct($message);
    }
}
