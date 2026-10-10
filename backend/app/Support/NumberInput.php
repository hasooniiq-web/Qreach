<?php

namespace App\Support;

/** Numbers typed with thousands separators or Arabic-Indic digits → plain digits. */
class NumberInput
{
    private const DIGITS = ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9'];

    public static function clean(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return preg_replace('/[,\x{066C}\s]/u', '', strtr($value, self::DIGITS));
    }
}