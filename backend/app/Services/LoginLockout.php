<?php

namespace App\Services;

use App\Exceptions\AccountLockedException;
use App\Models\AuthLockout;
use Illuminate\Support\Facades\DB;

/**
 * Escalating lockout for wrong secrets (passwords, one-time codes, authenticator codes).
 *
 *   3 wrong in a row → locked 1 hour; after that 3 more → 10 hours; then 24 h, 3 days, 7 days.
 *
 * Keyed by the account (phone / email), never by IP: many customers share one mobile
 * carrier address, so locking an address would lock strangers out. The same rule applies
 * to an unknown phone or email, so the answer never reveals whether an account exists.
 * Checked *before* the secret is compared, so a lock cannot be "guessed through".
 */
class LoginLockout
{
    public const CUSTOMER = 'customer';

    public const CUSTOMER_OTP = 'customer_otp';

    public const ADMIN = 'admin';

    public const ADMIN_TOTP = 'admin_totp';

    /** Throws while the account is locked. */
    public function assertNotLocked(string $scope, string $identifier): void
    {
        $row = AuthLockout::where('scope', $scope)->where('identifier', $this->key($identifier))->first();

        if ($row && $row->locked_until && $row->locked_until->isFuture()) {
            throw new AccountLockedException($row->locked_until, max(1, (int) now()->diffInSeconds($row->locked_until, false)));
        }
    }

    /**
     * Counts one wrong secret.
     *
     * @return array{locked: bool, attempts_left: int, locked_until: ?\Carbon\CarbonInterface, retry_after: int}
     */
    public function recordFailure(string $scope, string $identifier): array
    {
        $key = $this->key($identifier);
        $threshold = max(1, (int) config('security.lockout.threshold', 3));
        $durations = (array) config('security.lockout.durations', [3600]);

        return DB::transaction(function () use ($scope, $key, $threshold, $durations) {
            $row = $this->lockedRow($scope, $key);
            $now = now();

            // Already locked (a parallel request got here first): the answer is the same, nothing is counted.
            if ($row->locked_until && $row->locked_until->isFuture()) {
                return $this->state(true, 0, $row);
            }

            if ($row->last_failure_at) {
                if ($row->last_failure_at->lt($now->copy()->subDays((int) config('security.lockout.decay_days', 30)))) {
                    $row->level = 0;
                    $row->failures = 0;
                } elseif ($row->last_failure_at->lt($now->copy()->subSeconds((int) config('security.lockout.streak_window', 86400)))) {
                    $row->failures = 0;
                }
            }

            $row->failures++;
            $row->last_failure_at = $now;

            if ($row->failures >= $threshold) {
                $seconds = (int) $durations[min($row->level, count($durations) - 1)];
                $row->locked_until = $now->copy()->addSeconds($seconds);
                $row->level++;
                $row->failures = 0;
                $row->save();

                return $this->state(true, 0, $row);
            }

            $row->save();

            return $this->state(false, $threshold - $row->failures, $row);
        });
    }

    /** A correct secret: the history starts clean. */
    public function clear(string $scope, string $identifier): void
    {
        AuthLockout::where('scope', $scope)->where('identifier', $this->key($identifier))->delete();
    }

    /** Housekeeping for the scheduler: rows that no longer lock anyone and have gone quiet. */
    public function prune(): int
    {
        $cutoff = now()->subDays((int) config('security.lockout.decay_days', 30));

        return AuthLockout::query()
            ->where(fn ($q) => $q->whereNull('locked_until')->orWhere('locked_until', '<', now()))
            ->where(fn ($q) => $q->whereNull('last_failure_at')->orWhere('last_failure_at', '<', $cutoff))
            ->delete();
    }

    private function lockedRow(string $scope, string $key): AuthLockout
    {
        // INSERT ... ON CONFLICT DO NOTHING: safe inside a PostgreSQL transaction when two
        // requests for the same account arrive in the same instant.
        AuthLockout::insertOrIgnore([
            'scope' => $scope, 'identifier' => $key, 'failures' => 0, 'level' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return AuthLockout::where('scope', $scope)->where('identifier', $key)->lockForUpdate()->firstOrFail();
    }

    /** @return array{locked: bool, attempts_left: int, locked_until: ?\Carbon\CarbonInterface, retry_after: int} */
    private function state(bool $locked, int $attemptsLeft, AuthLockout $row): array
    {
        return [
            'locked' => $locked,
            'attempts_left' => $attemptsLeft,
            'locked_until' => $row->locked_until,
            'retry_after' => $locked && $row->locked_until ? max(1, (int) now()->diffInSeconds($row->locked_until, false)) : 0,
        ];
    }

    private function key(string $identifier): string
    {
        return mb_strtolower(trim($identifier));
    }
}
