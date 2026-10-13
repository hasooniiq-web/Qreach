<?php

namespace App\Exceptions;

use Carbon\CarbonInterface;
use RuntimeException;

/** Too many wrong secrets: this account may not try again until `lockedUntil`. */
class AccountLockedException extends RuntimeException
{
    public function __construct(public readonly CarbonInterface $lockedUntil, public readonly int $retryAfter)
    {
        parent::__construct('تم إيقاف المحاولات مؤقتًا بسبب إدخال بيانات خاطئة عدة مرات. حاول مرة أخرى بعد '.self::humanize($retryAfter).'.');
    }

    /** "ساعة" / "10 ساعات" / "يوم" — the wait in words a customer reads at a glance. */
    public static function humanize(int $seconds): string
    {
        // Whole minutes, rounded up: 3599 s is "an hour", not "60 minutes".
        $seconds = (int) (ceil(max(1, $seconds) / 60) * 60);

        if ($seconds < 3600) {
            $m = max(1, (int) ceil($seconds / 60));

            return $m === 1 ? 'دقيقة' : ($m === 2 ? 'دقيقتين' : ($m <= 10 ? "{$m} دقائق" : "{$m} دقيقة"));
        }

        if ($seconds >= 86400) {
            $d = (int) ceil($seconds / 86400);

            return $d === 1 ? 'يوم' : ($d === 2 ? 'يومين' : ($d <= 10 ? "{$d} أيام" : "{$d} يومًا"));
        }

        $h = (int) ceil($seconds / 3600);

        return $h === 1 ? 'ساعة' : ($h === 2 ? 'ساعتين' : ($h <= 10 ? "{$h} ساعات" : "{$h} ساعة"));
    }
}
