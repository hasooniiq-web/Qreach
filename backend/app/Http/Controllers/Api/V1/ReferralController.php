<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ReferralException;
use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Services\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * "Invite a friend": the customer's own code and what it earned, and entering a
 * friend's code. Every rule (who may enter one, the reward, the cap) is decided here.
 */
class ReferralController extends Controller
{
    public function __construct(private readonly ReferralService $referrals) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json($this->present($request));
    }

    public function apply(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:32']]);

        try {
            $this->referrals->apply($request->user(), $data['code']);
        } catch (ReferralException $e) {
            return response()->json(['error' => ['code' => $e->errorCode, 'message' => $e->getMessage()]], $e->status);
        }

        return response()->json($this->present($request));
    }

    /** @return array<string, mixed> */
    private function present(Request $request): array
    {
        $user = $request->user();
        $code = $this->referrals->codeFor($user);

        return [
            'code' => ReferralService::format($code),
            'reward_days' => $this->referrals->rewardDays(),
            'enabled' => $this->referrals->rewardDays() > 0,
            // Can this customer still enter a friend's code?
            'can_apply' => $this->referrals->blockedReason($user) === null,
            'referred' => Referral::where('referred_id', $user->id)->exists(),
        ] + $this->referrals->stats($user);
    }
}
