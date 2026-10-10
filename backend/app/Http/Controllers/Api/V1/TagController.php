<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ActivateTagRequest;
use App\Http\Resources\TagResource;
use App\Models\Vehicle;
use App\Services\TagLifecycleService;
use App\Services\TagTokenGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function __construct(private readonly TagLifecycleService $lifecycle) {}

    /**
     * Customer-initiated one-time activation (section 11). Rate limited on
     * both the account and the IP (see 'qr-activation' limiter) — a customer
     * hammering this endpoint against tokens they don't have is exactly the
     * QR-guessing scenario this must resist.
     */
    public function activate(ActivateTagRequest $request): JsonResponse
    {
        $vehicle = Vehicle::where('uuid', $request->input('vehicle_id'))->firstOrFail();

        $this->authorize('update', $vehicle);

        $tag = $this->lifecycle->activate($request->input('token'), $vehicle, $request->user());

        return (new TagResource($tag))->response()->setStatusCode(201);
    }

    /**
     * The owner removes the vehicle's code ("delete, then add a new one").
     * The old tag is revoked, not returned to stock: a code that has been
     * detached can't be re-linked by anyone.
     */
    public function destroy(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->authorize('update', $vehicle);

        $tag = $vehicle->tags()->where('status', 'assigned')->first();

        if (! $tag) {
            return response()->json(['error' => ['code' => 'NO_TAG', 'message' => 'لا يوجد رمز مرتبط بهذه المركبة.']], 404);
        }

        $this->lifecycle->revoke($tag, 'removed by owner', 'customer', $request->user()->id);

        return response()->json(['message' => 'تم حذف الرمز من المركبة.']);
    }

    public function show(Request $request, string $token): JsonResponse
    {
        $token = TagTokenGenerator::normalize($token);
        $vehicle = Vehicle::where('user_id', $request->user()->id)
            ->whereHas('tags', fn ($q) => $q->where('public_token', $token))
            ->firstOrFail();

        $this->authorize('view', $vehicle);

        $tag = $vehicle->tags()->where('public_token', $token)->firstOrFail();

        return (new TagResource($tag))->response();
    }
}
