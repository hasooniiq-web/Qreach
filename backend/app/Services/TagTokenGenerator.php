<?php

namespace App\Services;

use App\Models\Tag;

/**
 * Section 10: public tokens must be cryptographically random, high entropy,
 * unpredictable, and unrelated to any internal id. 32 random bytes
 * (256 bits) base64url-encoded gives ~43 characters — comfortably beyond
 * any plausible brute-force/guessing threat model for a public URL token,
 * with a huge security margin (not "128-bit because QR" — that conflates
 * QR visual capacity with token entropy, which the spec explicitly warns
 * against in section 10/19).
 */
class TagTokenGenerator
{
    public function generatePublicToken(): string
    {
        do {
            $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        } while (Tag::where('public_token', $token)->exists());

        return $token;
    }

    public function generateSerialNumber(string $type, int $sequence): string
    {
        $prefix = $type === 'nfc' ? 'ST-N' : 'ST-Q';
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
