<?php

namespace App\Services;

use App\Exceptions\VoucherException;
use App\Models\Voucher;
use App\Models\VoucherBatch;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Support\NumberInput;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Activation vouchers: generation, redemption and revocation.
 *
 * A serial is 16 digits: 15 random digits (from the OS CSPRNG) and a Luhn check
 * digit, so a mistyped serial is rejected before any lookup. It is stored
 * encrypted with a keyed hash beside it; the hash is what redemption looks up.
 *
 * Redemption is guarded three ways: a per-account attempt limit, a lockout after
 * repeated unknown serials (guessing), and an atomic single-use update under a row
 * lock so two phones can never redeem the same serial.
 */
class VoucherService
{
    public const MAX_PER_BATCH = 5000;

    private const CHUNK = 500;

    /** Attempts per minute per account, and unknown serials per hour before a lockout. */
    private const ATTEMPTS_PER_MINUTE = 6;

    private const FAILURES_BEFORE_LOCK = 10;

    private const LOCK_MINUTES = 60;

    public function __construct(private readonly SubscriptionManager $subscriptions) {}

    // ---- Serial format -----------------------------------------------------

    /** Digits only (Arabic-Indic digits, spaces and dashes accepted); null unless exactly 16. */
    public static function normalize(string $input): ?string
    {
        $clean = (string) NumberInput::clean($input);
        $digits = preg_replace('/\D+/', '', $clean) ?? '';

        return strlen($digits) === 16 ? $digits : null;
    }

    public static function checkDigit(string $fifteenDigits): int
    {
        $sum = 0;
        $double = true; // the rightmost payload digit is doubled: the check digit will sit to its right
        for ($i = strlen($fifteenDigits) - 1; $i >= 0; $i--) {
            $d = (int) $fifteenDigits[$i];
            if ($double) {
                $d *= 2;
                if ($d > 9) {
                    $d -= 9;
                }
            }
            $sum += $d;
            $double = ! $double;
        }

        return (10 - ($sum % 10)) % 10;
    }

    public static function isValid(string $sixteenDigits): bool
    {
        return preg_match('/^\d{16}$/', $sixteenDigits) === 1
            && self::checkDigit(substr($sixteenDigits, 0, 15)) === (int) $sixteenDigits[15];
    }

    public static function format(string $sixteenDigits): string
    {
        return trim(chunk_split($sixteenDigits, 4, ' '));
    }

    public static function hash(string $sixteenDigits): string
    {
        return hash_hmac('sha256', $sixteenDigits, (string) config('app.key'));
    }

    private function randomSerial(): string
    {
        $payload = '';
        for ($i = 0; $i < 15; $i++) {
            $payload .= (string) random_int(0, 9);
        }

        return $payload.self::checkDigit($payload);
    }

    // ---- Generation --------------------------------------------------------

    public function generate(
        SubscriptionPlan $plan,
        int $quantity,
        int $durationDays,
        ?string $recipient,
        ?string $notes,
        ?\DateTimeInterface $expiresAt,
        ?int $adminId,
    ): VoucherBatch {
        if ($quantity < 1 || $quantity > self::MAX_PER_BATCH) {
            throw new \InvalidArgumentException('Quantity must be between 1 and '.self::MAX_PER_BATCH.'.');
        }
        if ($plan->isFree()) {
            throw new \InvalidArgumentException('Vouchers activate a paid plan.');
        }

        return DB::transaction(function () use ($plan, $quantity, $durationDays, $recipient, $notes, $expiresAt, $adminId) {
            $batch = VoucherBatch::create([
                'batch_code' => $this->nextBatchCode(),
                'plan_id' => $plan->id,
                'duration_days' => $durationDays,
                'quantity' => $quantity,
                'recipient' => $recipient ?: null,
                'notes' => $notes ?: null,
                'expires_at' => $expiresAt,
                'created_by' => $adminId,
            ]);

            $made = 0;
            while ($made < $quantity) {
                $want = min(self::CHUNK, $quantity - $made);
                $rows = [];
                $hashes = [];
                $now = now();

                while (count($rows) < $want) {
                    $code = $this->randomSerial();
                    $hash = self::hash($code);
                    if (isset($hashes[$hash])) {
                        continue;
                    }
                    $hashes[$hash] = true;
                    $rows[] = [
                        'batch_id' => $batch->id,
                        'code_hash' => $hash,
                        'code' => Crypt::encryptString($code),
                        'last4' => substr($code, -4),
                        'status' => Voucher::AVAILABLE,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                // A serial that already exists elsewhere is astronomically unlikely, but uniqueness is
                // a database constraint, so drop any clash and top the chunk up on the next pass.
                $existing = Voucher::whereIn('code_hash', array_keys($hashes))->pluck('code_hash')->all();
                if ($existing !== []) {
                    $rows = array_values(array_filter($rows, fn ($r) => ! in_array($r['code_hash'], $existing, true)));
                }
                if ($rows !== []) {
                    Voucher::insert($rows);
                }
                $made += count($rows);
            }

            AuditLogger::log('voucher.batch_generated', 'admin', $adminId, 'VoucherBatch', $batch->id, 'success', [
                'quantity' => $quantity, 'plan' => $plan->slug, 'days' => $durationDays, 'recipient' => $recipient,
            ]);

            return $batch->fresh();
        });
    }

    private function nextBatchCode(): string
    {
        $prefix = 'VB-'.now()->format('Ymd');
        $n = VoucherBatch::where('batch_code', 'like', $prefix.'-%')->count() + 1;

        do {
            $code = sprintf('%s-%03d', $prefix, $n++);
        } while (VoucherBatch::where('batch_code', $code)->exists());

        return $code;
    }

    // ---- Redemption --------------------------------------------------------

    /**
     * @param  string  $actorType  customer (the app) | admin (staff activating for a user)
     *
     * @throws VoucherException
     */
    public function redeem(User $user, string $input, string $actorType = 'customer', ?int $actorId = null): Voucher
    {
        $actorId ??= $user->id;
        $throttleKey = 'voucher-redeem:'.$actorType.':'.$actorId;
        $failKey = 'voucher-fail:'.$actorType.':'.$actorId;

        if (Cache::has($failKey.':locked')) {
            throw new VoucherException('تجاوزت عدد المحاولات المسموح بها. حاول بعد ساعة.', 'VOUCHER_LOCKED', 429);
        }
        if (RateLimiter::tooManyAttempts($throttleKey, self::ATTEMPTS_PER_MINUTE)) {
            throw new VoucherException('محاولات كثيرة خلال وقت قصير. انتظر دقيقة ثم حاول مجددًا.', 'VOUCHER_THROTTLED', 429);
        }
        RateLimiter::hit($throttleKey, 60);

        $code = self::normalize($input);
        if ($code === null || ! self::isValid($code)) {
            // A typo: nothing was looked up, so it does not count as a guess.
            throw new VoucherException('رقم القسيمة غير صحيح. تأكد أنه 16 رقمًا وأعد المحاولة.', 'VOUCHER_INVALID_FORMAT');
        }

        $voucher = Voucher::with('batch.plan')->where('code_hash', self::hash($code))->first();
        if (! $voucher) {
            $this->recordFailure($failKey, $actorType, $actorId);

            throw new VoucherException('رقم القسيمة غير موجود.', 'VOUCHER_INVALID');
        }

        return DB::transaction(function () use ($voucher, $user, $actorType, $actorId) {
            $locked = Voucher::with('batch.plan')->where('id', $voucher->id)->lockForUpdate()->first();

            if ($locked->status === Voucher::REDEEMED) {
                throw new VoucherException('هذه القسيمة مستخدمة من قبل.', 'VOUCHER_USED');
            }
            if ($locked->status === Voucher::REVOKED) {
                throw new VoucherException('هذه القسيمة ملغاة ولا يمكن استخدامها.', 'VOUCHER_REVOKED');
            }
            if ($locked->batch->isExpired()) {
                throw new VoucherException('انتهت صلاحية هذه القسيمة.', 'VOUCHER_EXPIRED');
            }

            $subscription = $this->subscriptions->activateFromVoucher($user, $locked, $actorType === 'admin' ? $actorId : null);

            // The conditional update is the real single-use guarantee, beyond the row lock.
            $updated = Voucher::where('id', $locked->id)
                ->whereIn('status', [Voucher::AVAILABLE, Voucher::DISTRIBUTED])
                ->update([
                    'status' => Voucher::REDEEMED,
                    'redeemed_by' => $user->id,
                    'redeemed_at' => now(),
                    'subscription_id' => $subscription->id,
                    'updated_at' => now(),
                ]);

            if ($updated === 0) {
                throw new VoucherException('هذه القسيمة مستخدمة من قبل.', 'VOUCHER_USED');
            }

            AuditLogger::log('voucher.redeemed', $actorType, $actorId, 'Voucher', $locked->id, 'success', [
                'user_id' => $user->id, 'batch' => $locked->batch->batch_code, 'last4' => $locked->last4,
                'plan' => $locked->batch->plan->slug, 'days' => $locked->batch->duration_days,
            ]);

            return $locked->fresh(['batch.plan']);
        });
    }

    private function recordFailure(string $failKey, string $actorType, int $actorId): void
    {
        $count = (int) Cache::get($failKey, 0) + 1;
        Cache::put($failKey, $count, now()->addMinutes(self::LOCK_MINUTES));

        if ($count >= self::FAILURES_BEFORE_LOCK) {
            Cache::put($failKey.':locked', true, now()->addMinutes(self::LOCK_MINUTES));
            Cache::forget($failKey);

            SecurityEventLogger::log(
                SecurityEventLogger::QR_GUESSING,
                'warning',
                $actorType,
                $actorId,
                ['reason' => 'voucher_guessing', 'failures' => $count],
            );
        }
    }

    // ---- Revocation ---------------------------------------------------------

    public function revoke(Voucher $voucher, string $reason, ?int $adminId): bool
    {
        return DB::transaction(function () use ($voucher, $reason, $adminId) {
            $updated = Voucher::where('id', $voucher->id)
                ->whereIn('status', [Voucher::AVAILABLE, Voucher::DISTRIBUTED])
                ->update([
                    'status' => Voucher::REVOKED,
                    'revoked_at' => now(),
                    'revoke_reason' => Str::limit($reason, 250, ''),
                    'revoked_by' => $adminId,
                    'updated_at' => now(),
                ]);

            if ($updated) {
                AuditLogger::log('voucher.revoked', 'admin', $adminId, 'Voucher', $voucher->id, 'success', [
                    'reason' => $reason, 'last4' => $voucher->last4,
                ]);
            }

            return $updated > 0;
        });
    }

    /** Revokes every unused voucher of a batch; returns how many were revoked. */
    public function revokeBatch(VoucherBatch $batch, string $reason, ?int $adminId): int
    {
        return DB::transaction(function () use ($batch, $reason, $adminId) {
            $count = Voucher::where('batch_id', $batch->id)
                ->whereIn('status', [Voucher::AVAILABLE, Voucher::DISTRIBUTED])
                ->update([
                    'status' => Voucher::REVOKED,
                    'revoked_at' => now(),
                    'revoke_reason' => Str::limit($reason, 250, ''),
                    'revoked_by' => $adminId,
                    'updated_at' => now(),
                ]);

            AuditLogger::log('voucher.batch_revoked', 'admin', $adminId, 'VoucherBatch', $batch->id, 'success', [
                'count' => $count, 'reason' => $reason,
            ]);

            return $count;
        });
    }
}
