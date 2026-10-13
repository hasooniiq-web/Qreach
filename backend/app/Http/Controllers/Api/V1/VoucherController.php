<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\VoucherException;
use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use App\Services\PlanEntitlements;
use App\Services\VoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Redeeming an activation voucher from the app: type the 16 digits or scan the QR.
 * Everything that matters — single use, expiry, revocation, guessing limits — is
 * enforced in VoucherService; the app only forwards what was typed or scanned.
 */
class VoucherController extends Controller
{
    public function __construct(
        private readonly VoucherService $vouchers,
        private readonly PlanEntitlements $plans,
    ) {}

    public function redeem(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:64']]);
        $user = $request->user();

        try {
            $voucher = $this->vouchers->redeem($user, $data['code']);
        } catch (VoucherException $e) {
            return response()->json(['error' => ['code' => $e->errorCode, 'message' => $e->getMessage()]], $e->status);
        }

        $subscription = $this->plans->activeSubscription($user);

        return response()->json([
            'message' => 'تم تفعيل باقة «'.$voucher->batch->plan->nameIn($request->getPreferredLanguage(\App\Models\SubscriptionPlan::LANGUAGES)).'» بنجاح.',
            'plan' => new PlanResource($this->plans->planFor($user)),
            'days' => (int) $voucher->batch->duration_days,
            'current_period_ends_at' => $subscription?->current_period_ends_at?->toIso8601String(),
        ]);
    }
}
