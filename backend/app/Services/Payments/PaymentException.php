<?php

namespace App\Services\Payments;

/** A payment request that cannot proceed, with the API error code and HTTP status to report. */
class PaymentException extends \RuntimeException
{
    public function __construct(public readonly string $errorCode, string $message, public readonly int $status = 422)
    {
        parent::__construct($message);
    }
}
