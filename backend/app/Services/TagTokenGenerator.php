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

    /** Human-readable serial printed on the physical tag, e.g. ST-Q-2026-000048. */
    public function formatSerial(string $type, int $sequence): string
    {
        return sprintf('%s-%s-%06d', $this->serialPrefix($type), now()->format('Y'), $sequence);
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
        $year = now()->format('Y');
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
