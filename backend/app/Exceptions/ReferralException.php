<?php

namespace App\Exceptions;

use RuntimeException;

/** An invite code cannot be used; `errorCode` is the stable code the app maps to its own wording. */
class ReferralException extends RuntimeException
{
    public function __construct(string $message, public readonly string $errorCode, public readonly int $status = 422)
    {
        parent::__construct($message);
    }
}
