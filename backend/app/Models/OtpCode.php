<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * `code_hash` is an HMAC of the plaintext OTP — the plaintext is never
 * persisted anywhere (section 23/27).
 */
class OtpCode extends Model
{
    protected $fillable = ['phone', 'code_hash', 'purpose', 'attempts', 'max_attempts', 'expires_at', 'request_ip', 'consumed_at'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function attemptsExhausted(): bool
    {
        return $this->attempts >= $this->max_attempts;
    }
}
