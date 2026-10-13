<?php

namespace App\Exceptions;

use RuntimeException;

/** Too many one-time codes were requested (for this number, this address, or in total today). */
class OtpLimitException extends RuntimeException
{
    public function __construct(public readonly int $retryAfter)
    {
        parent::__construct('تم الوصول إلى الحد الأقصى لطلبات رمز التحقق. حاول مرة أخرى بعد '.AccountLockedException::humanize($retryAfter).'.');
    }
}
