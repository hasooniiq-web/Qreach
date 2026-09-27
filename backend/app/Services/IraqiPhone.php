<?php

namespace App\Services;

/**
 * The system is Iraq-only, so no phone field ever asks a person to type a
 * country code. Iraqi mobile numbers are written two ways: with the leading
 * trunk zero used for local dialing ("07701234567", 11 digits) or without it
 * ("7701234567", 10 digits) — both mean the same subscriber, and every phone
 * field (login, emergency contact, the public contact page, the phone/whatsapp
 * contact platforms) must accept either form and normalize to "+964..." for
 * storage and matching.
 */
class IraqiPhone
{
    /** Strips a single leading trunk zero from a local number, if present. */
    public static function stripTrunkZero(string $raw): string
    {
        $value = trim($raw);

        return (strlen($value) === 11 && str_starts_with($value, '07')) ? substr($value, 1) : $value;
    }

    /** True for a local Iraqi mobile number: "7XXXXXXXXX" or "07XXXXXXXXX". */
    public static function isValidLocal(string $raw): bool
    {
        return (bool) preg_match('/^7\d{9}$/', self::stripTrunkZero($raw));
    }

    /** Builds "+964XXXXXXXXXX" from a local number in either form. */
    public static function toE164(string $local): string
    {
        return '+964'.self::stripTrunkZero($local);
    }

    /**
     * Accepts a local Iraqi number in either form and returns "+964...".
     * A value already written with a country code (from data stored before
     * this normalization existed, or the rare non-Iraqi contact) is kept as
     * an international number unchanged. Returns null for anything else.
     */
    public static function normalize(string $raw): ?string
    {
        $value = trim($raw);
        if ($value === '') {
            return null;
        }

        if (self::isValidLocal($value)) {
            return self::toE164($value);
        }

        $withPlus = str_starts_with($value, '+') ? $value : '+'.$value;

        return preg_match('/^\+[1-9]\d{7,14}$/', $withPlus) ? $withPlus : null;
    }
}
