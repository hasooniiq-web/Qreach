<?php

namespace App\Exceptions;

use RuntimeException;

/** A voucher cannot be redeemed; `errorCode` is the stable code the app maps to its own wording. */
class VoucherException extends RuntimeException
{
    public function __construct(string $message, public readonly string $errorCode, public readonly int $status = 422)
    {
        parent::__construct($message);
    }
}
