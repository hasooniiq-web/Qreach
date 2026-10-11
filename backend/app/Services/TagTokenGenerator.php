<?php

namespace App\Services;

use App\Models\Tag;

/**
 * Public tokens (the part after /t/ in a sticker's or NFC chip's URL).
 *
 * Format: Crockford Base32 of 128 random bits = 26 characters, e.g.
 * `7K3QZ0M4X9TBN2R8D5WHVYC1AJ`.
 *
 *  - Randomness: `random_bytes()` — the operating system's CSPRNG. Never a
 *    predictable generator.
 *  - Strength: 128 bits. Guessing a live token is infeasible (2^128 space), and
 *    the scan endpoints are rate-limited and log guessing attempts on top of that.
 *  - Alphabet `0-9A-Z` minus I, L, O, U: only digits and capital letters, so a
 *    token ALWAYS starts with a letter or digit — never `-`, `=`, `+`, `@` (a
 *    spreadsheet reads a leading one of those as a formula and shows #NAME?) —
 *    and it has no look-alike characters when typed by hand.
 *  - Shorter than the previous 43-character Base64URL (256-bit) tokens, which means
 *    a smaller, faster-scanning QR grid.
 *
 * Tokens issued before this format (43 characters) keep working exactly as
 * printed; only newly generated batches use the new format.
 */
class TagTokenGenerator
{
    private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    private const LENGTH = 26;

    private const SERIAL_PAYLOAD_LENGTH = 6;

    public function generatePublicToken(): string
    {
        do {
            $token = $this->randomToken();
        } while (Tag::where('public_token', $token)->exists());

        return $token;
    }

    /**
     * $count unique tokens in one go: duplicates are removed in memory and the
     * database is asked once for any that already exist, instead of once per token.
     *
     * @return list<string>
     */
    public function generateMany(int $count): array
    {
        $tokens = [];

        while (count($tokens) < $count) {
            while (count($tokens) < $count) {
                $tokens[$this->randomToken()] = true;
            }

            $taken = Tag::whereIn('public_token', array_keys($tokens))->pluck('public_token')->all();
            foreach ($taken as $token) {
                unset($tokens[$token]);
            }
        }

        return array_keys($tokens);
    }

    /**
     * What a person (or a lowercase-folding system) typed → the stored form.
     * Applies only to the new 26-character format; older 43-character tokens are
     * case-sensitive Base64URL and are returned untouched.
     */
    public static function normalize(string $token): string
    {
        if (strlen($token) !== self::LENGTH || ! ctype_alnum($token)) {
            return $token;
        }

        return strtr(strtoupper($token), ['O' => '0', 'I' => '1', 'L' => '1']);
    }

    private function randomToken(): string
    {
        $bytes = random_bytes(16); // 128 bits
        $bits = '';
        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }
        $bits .= '00'; // 128 -> 130 bits = 26 groups of 5

        $token = '';
        foreach (str_split($bits, 5) as $group) {
            $token .= self::ALPHABET[bindec($group)];
        }

        return $token;
    }

    /**
     * Serials for new codes: `ST-C26-8K7M2P-4`.
     *
     *   ST   product prefix        C  category (C = unified QR+NFC, Q, N)
     *   26   year (2 digits)       8K7M2P  6 random Crockford Base32 characters
     *   4    check character (Luhn mod 32 over everything before it)
     *
     * The check character catches a mistyped or misread serial before any lookup.
     * The serial is printed on the sticker and is NOT a secret — it only finds a
     * record. The thing that opens the public page is the long random token in
     * the QR/NFC address, never this value.
     *
     * @return list<string>
     */
    public function generateSerials(string $type, int $count): array
    {
        $category = $this->serialCategory($type);
        $year = now()->format('y');
        $serials = [];

        while (count($serials) < $count) {
            while (count($serials) < $count) {
                $payload = '';
                for ($i = 0; $i < self::SERIAL_PAYLOAD_LENGTH; $i++) {
                    $payload .= self::ALPHABET[random_int(0, 31)];
                }
                $serials[self::composeSerial($category, $year, $payload)] = true;
            }

            $taken = Tag::whereIn('serial_number', array_keys($serials))->pluck('serial_number')->all();
            foreach ($taken as $serial) {
                unset($serials[$serial]);
            }
        }

        return array_keys($serials);
    }

    public static function composeSerial(string $category, string $year, string $payload): string
    {
        return sprintf('ST-%s%s-%s-%s', $category, $year, $payload, self::checkCharacter('ST'.$category.$year.$payload));
    }

    /** Luhn mod-32 check character over characters that all belong to the alphabet. */
    public static function checkCharacter(string $body): string
    {
        $n = strlen(self::ALPHABET);
        $factor = 2;
        $sum = 0;

        for ($i = strlen($body) - 1; $i >= 0; $i--) {
            $code = strpos(self::ALPHABET, $body[$i]);
            if ($code === false) {
                throw new \InvalidArgumentException('Character outside the serial alphabet: '.$body[$i]);
            }

            $addend = $factor * $code;
            $factor = $factor === 2 ? 1 : 2;
            $sum += intdiv($addend, $n) + ($addend % $n);
        }

        return self::ALPHABET[($n - ($sum % $n)) % $n];
    }

    /** True for a serial in the new format whose check character matches. */
    public static function isValidSerial(string $serial): bool
    {
        if (! preg_match('/^ST-([CQN])(\d{2})-([0-9A-HJKMNP-TV-Z]{6})-([0-9A-HJKMNP-TV-Z])$/', strtoupper($serial), $m)) {
            return false;
        }

        return self::composeSerial($m[1], $m[2], $m[3]) === strtoupper($serial);
    }

    private function serialCategory(string $type): string
    {
        return match ($type) {
            'nfc' => 'N',
            'qr' => 'Q',
            default => 'C',
        };
    }

    /**
     * LEGACY (existing rows keep their serial): ST-C-20261010-000150. A unified code carries the
     * full generation date — ST-C-20261010-000150 (day, month, year) — so when a code
     * was made can be read off the sticker; older kinds keep ST-Q-2026-000048.
     * The number is at least 6 digits and simply grows past that (ids are 64-bit),
     * so there is no ceiling at millions of codes.
     */
    public function formatSerial(string $type, int $sequence): string
    {
        return sprintf('%s-%s-%06d', $this->serialPrefix($type), $this->datePart($type), $sequence);
    }

    private function datePart(string $type): string
    {
        return $type === 'code' ? now()->format('Ymd') : now()->format('Y');
    }

    /** ST-C = unified code (QR + NFC); ST-Q / ST-N are the older single-purpose kinds. */
    private function serialPrefix(string $type): string
    {
        return match ($type) {
            'code' => 'ST-C',
            'nfc' => 'ST-N',
            default => 'ST-Q',
        };
    }

    public function generateSerialNumber(string $type, int $sequence): string
    {
        $prefix = $this->serialPrefix($type);
        $year = $this->datePart($type);
        $serial = sprintf('%s-%s-%06d', $prefix, $year, $sequence);

        // Extremely unlikely to collide (sequence is batch-scoped and
        // monotonic), but never trust an assumption where uniqueness is a
        // database constraint away from being verified.
        while (Tag::where('serial_number', $serial)->exists()) {
            $sequence++;
            $serial = sprintf('%s-%s-%06d', $prefix, $year, $sequence);
        }

        return $serial;
    }
}
