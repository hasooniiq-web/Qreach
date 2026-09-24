<?php

namespace App\Exceptions;

use Exception;

class OtpCooldownException extends Exception
{
    public function __construct(public readonly int $secondsRemaining)
    {
        parent::__construct("يرجى الانتظار {$secondsRemaining} ثانية قبل طلب رمز جديد.");
    }
}
