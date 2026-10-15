<?php

namespace App\Support;

/**
 * What an admin types in a phone search box: "07727009040", "7727009040", "+964 772 700 9040",
 * "9647727009040" or Arabic-Indic digits — all of them are the same number. Phone numbers are
 * stored as +964…, so the match is on the national digits (no country code, no leading zero).
 */
final class PhoneSearch
{
    private const ARABIC_DIGITS = [
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
    ];

    /** A LIKE pattern for a phone-looking query (4+ digits), or null when it is a name / plate / text. */
    public static function pattern(string $query): ?string
    {
        $query = trim($query);

        // Letters mean it is not a number being typed (a name, a plate with a letter, a receipt).
        if ($query === '' || preg_match('/[\p{L}]/u', strtr($query, self::ARABIC_DIGITS)) === 1) {
            return null;
        }

        $digits = preg_replace('/\D/', '', strtr($query, self::ARABIC_DIGITS));
        if (strlen($digits) < 4) {
            return null;
        }

        $national = ltrim(preg_replace('/^(00)?964/', '', $digits), '0');

        return $national === '' ? null : '%'.$national.'%';
    }
}
