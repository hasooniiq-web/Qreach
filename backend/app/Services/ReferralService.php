<?php

namespace App\Services;

use App\Exceptions\ReferralException;
use App\Models\Referral;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\User;
use App\Notifications\ReferralRewardNotification;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Invite a friend. Every customer has a personal code. A friend who is new to paid plans
 * enters it once. When the inviter's count of accepted invitations reaches the required
 * number (two), the inviter is given one month of Plus — once per account, ever.
 */
class ReferralService
{
    /** Crockford base-32: no I, L, O, U, so a code read aloud or from a screenshot survives. */
    private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    private const CODE_LENGTH = 8;

    /** Days given to the inviter per friend; 0 switches the programme off. */
    public function rewardDays(): int
    {
        return max(0, (int) SystemSetting::get('referral_reward_days', 30));
    }

    /** How many friends one account may invite in total. */
    public function maxInvites(): int
    {
        return max(0, (int) SystemSetting::get('referral_max_invites', 2));
    }

    /** How many accepted invitations earn the inviter the reward (once per account). */
    public function invitesRequired(): int
    {
        return max(1, (int) SystemSetting::get('referral_invites_required', 2));
    }

    /** The customer's own code, made on first use. */
    public function codeFor(User $user): string
    {
        if ($user->referral_code) {
            return $user->referral_code;
        }

        for ($attempt = 0; $attempt < 8; $attempt++) {
            $code = $this->randomCode();

            try {
                $updated = User::whereKey($user->id)->whereNull('referral_code')->update(['referral_code' => $code]);
            } catch (UniqueConstraintViolationException) {
                continue; // another customer got this code first
            }

            $user->referral_code = $updated ? $code : (string) User::whereKey($user->id)->value('referral_code');

            return $user->referral_code;
        }

        throw new \RuntimeException('Could not allocate a referral code.');
    }

    /** "ab12-cd34" / "ab12 cd34" / lowercase / O-for-zero → the stored form, or null when it cannot be a code. */
    public static function normalize(string $input): ?string
    {
        $code = strtoupper((string) preg_replace('/[\s\-]+/', '', $input));
        $code = strtr($code, ['O' => '0', 'I' => '1', 'L' => '1']);

        return preg_match('/^['.self::ALPHABET.']{'.self::CODE_LENGTH.'}$/', $code) === 1 ? $code : null;
    }

    public static function format(string $code): string
    {
        return substr($code, 0, 4).'-'.substr($code, 4);
    }

    /** Why this customer cannot enter an invite code right now, or null when they can. */
    public function blockedReason(User $user): ?string
    {
        if ($this->rewardDays() === 0) {
            return 'disabled';
        }

        if (Referral::where('referred_id', $user->id)->exists()) {
            return 'already';
        }

        // The programme brings in new payers: someone who already paid cannot be "invited".
        $paid = Subscription::where('user_id', $user->id)
            ->where('status', '!=', 'trialing')
            ->whereHas('plan', fn ($q) => $q->where('price_cents', '>', 0))
            ->exists();

        return $paid ? 'not_new' : null;
    }

    /**
     * @throws ReferralException
     */
    public function apply(User $user, string $input): Referral
    {
        if ($reason = $this->blockedReason($user)) {
            throw match ($reason) {
                'already' => new ReferralException('أدخلت رمز دعوة من قبل، ولا يمكن إدخال رمز ثانٍ.', 'REFERRAL_ALREADY', 409),
                'not_new' => new ReferralException('رمز الدعوة للمستخدمين الذين لم يشتركوا بعد.', 'REFERRAL_NOT_NEW', 409),
                default => new ReferralException('الدعوات غير متاحة حاليًا.', 'REFERRAL_UNAVAILABLE', 422),
            };
        }

        $code = self::normalize($input);
        $inviter = $code ? User::where('referral_code', $code)->where('status', 'active')->first() : null;

        if (! $inviter) {
            throw new ReferralException('رمز الدعوة غير صحيح. تأكد من الرمز وأعد المحاولة.', 'REFERRAL_INVALID', 422);
        }

        if ($inviter->id === $user->id) {
            throw new ReferralException('لا يمكنك استخدام رمز دعوتك أنت.', 'REFERRAL_SELF', 422);
        }

        // A invited B, so B's code cannot be used by A.
        if (Referral::where('referrer_id', $user->id)->where('referred_id', $inviter->id)->exists()) {
            throw new ReferralException('رمز الدعوة غير صحيح. تأكد من الرمز وأعد المحاولة.', 'REFERRAL_INVALID', 422);
        }

        // Two invitations per account (a setting): after that the code no longer takes anyone new.
        if (Referral::where('referrer_id', $inviter->id)->count() >= $this->maxInvites()) {
            throw new ReferralException('وصل صاحب هذا الرمز إلى الحد الأقصى من الدعوات.', 'REFERRAL_FULL', 422);
        }

        try {
            $referral = Referral::create(['referrer_id' => $inviter->id, 'referred_id' => $user->id]);
        } catch (UniqueConstraintViolationException) {
            throw new ReferralException('أدخلت رمز دعوة من قبل، ولا يمكن إدخال رمز ثانٍ.', 'REFERRAL_ALREADY', 409);
        }

        AuditLogger::log('referral.applied', 'customer', $user->id, 'Referral', $referral->id, 'success', [
            'referrer_id' => $inviter->id,
        ]);

        $this->rewardInviterIfDue($inviter, $referral);

        return $referral->fresh();
    }

    /**
     * One month of Plus the moment the inviter's accepted invitations reach the required
     * count. Never twice for the same account, however many friends join later.
     */
    private function rewardInviterIfDue(User $inviter, Referral $latest): void
    {
        $days = $this->rewardDays();
        if ($days === 0) {
            return;
        }

        DB::transaction(function () use ($inviter, $latest, $days) {
            // Serialise concurrent invitations of the same inviter.
            $locked = User::whereKey($inviter->id)->lockForUpdate()->first();
            if (! $locked || $locked->status !== 'active') {
                return;
            }

            $all = Referral::where('referrer_id', $inviter->id)->get();
            if ($all->where('status', 'rewarded')->isNotEmpty() || $all->count() < $this->invitesRequired()) {
                return;
            }

            app(SubscriptionManager::class)->grantReward($locked, $days);

            Referral::where('referrer_id', $inviter->id)->update(['status' => 'rewarded', 'rewarded_at' => now()]);
            $latest->update(['reward_days' => $days]);

            AuditLogger::log('referral.rewarded', 'system', null, 'Referral', $latest->id, 'success', [
                'referrer_id' => $inviter->id, 'invitations' => $all->count(), 'days' => $days,
            ]);

            $locked->notify(new ReferralRewardNotification($days));
        });
    }

    /** @return array{invited: int, rewarded: int, earned_days: int} */
    public function stats(User $user): array
    {
        $rows = Referral::where('referrer_id', $user->id)->get(['status', 'reward_days']);

        return [
            'invited' => $rows->count(),
            'rewarded' => $rows->where('status', 'rewarded')->count(),
            'earned_days' => (int) $rows->where('status', 'rewarded')->sum('reward_days'),
            'max_invites' => $this->maxInvites(),
            'invites_required' => $this->invitesRequired(),
            'remaining_invites' => max(0, $this->maxInvites() - $rows->count()),
        ];
    }

    private function randomCode(): string
    {
        $out = '';
        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $out .= self::ALPHABET[random_int(0, 31)];
        }

        return $out;
    }
}
