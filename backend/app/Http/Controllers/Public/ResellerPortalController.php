<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\VoucherBatch;
use App\Services\SecurityEventLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/**
 * Public look-up for a reseller: batch number + access code -> how many vouchers of
 * the batch were activated and how many remain. Counts only: never a voucher number
 * and never who activated one. A wrong pair always gets the same answer, and repeated
 * wrong pairs lock the address out for an hour.
 */
class ResellerPortalController extends Controller
{
    private const FAILURES_BEFORE_LOCK = 8;

    public function form(): View
    {
        return view('public.reseller');
    }

    public function lookup(Request $request): View|\Illuminate\Http\Response
    {
        $data = $request->validate([
            'batch' => ['required', 'string', 'max:40'],
            'code' => ['required', 'string', 'max:16'],
        ]);

        $failKey = 'reseller-portal-fail:'.$request->ip();

        if (RateLimiter::tooManyAttempts($failKey, self::FAILURES_BEFORE_LOCK)) {
            return $this->failure(trans('site.portal.locked'), 429);
        }

        $batch = VoucherBatch::with('plan')
            ->where('batch_code', strtoupper(trim($data['batch'])))
            ->first();

        // Compared in constant time, and against a dummy when no batch matched, so a
        // right number with a wrong code looks exactly like a wrong number.
        $expected = $batch?->portal_code ?? str_repeat('0', 8);
        $given = VoucherBatch::normalizePortalCode($data['code']);

        if (! $batch || ! hash_equals($expected, $given)) {
            RateLimiter::hit($failKey, 3600);

            if (RateLimiter::attempts($failKey) === self::FAILURES_BEFORE_LOCK) {
                SecurityEventLogger::log(SecurityEventLogger::QR_GUESSING, 'warning', 'unknown', null, [
                    'reason' => 'reseller_portal_guessing',
                ], $request);
            }

            return $this->failure(trans('site.portal.wrong'), 422);
        }

        RateLimiter::clear($failKey);

        return view('public.reseller', ['result' => $this->summary($batch)]);
    }

    /** @return array<string, mixed> */
    private function summary(VoucherBatch $batch): array
    {
        $counts = $batch->counts();
        $unused = $counts['available'] + $counts['distributed'];
        $expired = $batch->isExpired();
        $tz = config('app.display_timezone');

        $redeemed = $batch->vouchers()->where('status', 'redeemed')->whereNotNull('redeemed_at');
        $last = (clone $redeemed)->max('redeemed_at');

        $daily = (clone $redeemed)
            ->where('redeemed_at', '>=', now()->subDays(14)->startOfDay())
            ->pluck('redeemed_at')
            ->map(fn ($at) => \Illuminate\Support\Carbon::parse($at)->timezone($tz)->format('Y-m-d'))
            ->countBy()
            ->sortKeysDesc()
            ->all();

        return [
            'batch_code' => $batch->batch_code,
            'plan' => $batch->plan->nameIn(app()->getLocale()),
            'duration_days' => (int) $batch->duration_days,
            'quantity' => (int) $batch->quantity,
            'redeemed' => $counts['redeemed'],
            'remaining' => $expired ? 0 : $unused,
            'expired' => $expired ? $unused : 0,
            'revoked' => $counts['revoked'],
            'created_at' => $batch->created_at?->copy()->timezone($tz)->format('Y-m-d'),
            'expires_at' => $batch->expires_at?->copy()->timezone($tz)->format('Y-m-d'),
            'last_activation' => $last ? \Illuminate\Support\Carbon::parse($last)->timezone($tz)->format('Y-m-d H:i') : null,
            'daily' => $daily,
        ];
    }

    private function failure(string $message, int $status): View|\Illuminate\Http\Response
    {
        return response()->view('public.reseller', ['error' => $message], $status);
    }
}
