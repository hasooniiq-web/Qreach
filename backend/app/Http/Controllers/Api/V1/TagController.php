<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ActivateTagRequest;
use App\Http\Resources\TagResource;
use App\Models\Vehicle;
use App\Services\TagLifecycleService;
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

    public function show(Request $request, string $token): JsonResponse
    {
        $vehicle = Vehicle::where('user_id', $request->user()->id)
            ->whereHas('tags', fn ($q) => $q->where('public_token', $token))
            ->firstOrFail();

        $this->authorize('view', $vehicle);

        $tag = $vehicle->tags()->where('public_token', $token)->firstOrFail();

        return (new TagResource($tag))->response();
    }
}
