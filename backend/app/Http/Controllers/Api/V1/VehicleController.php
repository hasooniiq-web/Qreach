<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreVehicleRequest;
use App\Http\Requests\Api\UpdateVehicleRequest;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $vehicles = $request->user()->vehicles()->with('tags')->latest()->paginate(20);

        return VehicleResource::collection($vehicles)->response();
    }

    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $user = $request->user();
        $plans = app(\App\Services\PlanEntitlements::class);
        $max = $plans->maxVehicles($user);

        // The free plan holds one vehicle; Plus holds three. The app turns this into
        // "activate the plan to add another" with a way to the subscription screen.
        if ($max !== null && $user->vehicles()->count() >= $max) {
            $plan = $plans->planFor($user);

            return response()->json(['error' => [
                'code' => 'VEHICLE_LIMIT',
                'message' => $plan->isFree()
                    ? 'الباقة المجانية تسمح بمركبة واحدة فقط. فعّل باقة Plus لإضافة حتى '.($this->plusMax()).' مركبات.'
                    : 'وصلت إلى الحد الأقصى للمركبات في باقتك ('.$max.').',
                'max_vehicles' => $max,
                'plan' => $plan->slug,
                'upgrade' => $plan->isFree(),
            ]], 403);
        }
        $vehicle = $request->user()->vehicles()->create($request->validated())->fresh();

        AuditLogger::log('vehicle.created', 'customer', $request->user()->id, 'Vehicle', $vehicle->id, 'success', [], $request);

        return (new VehicleResource($vehicle))->response()->setStatusCode(201);
    }

    private function plusMax(): int
    {
        return (int) (\App\Models\SubscriptionPlan::where('slug', 'plus')->value('max_vehicles') ?? 3);
    }

    public function show(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->authorize('view', $vehicle);

        return (new VehicleResource($vehicle->load('tags')))->response();
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): JsonResponse
    {
        $this->authorize('update', $vehicle);

        $vehicle->update($request->validated());

        AuditLogger::log('vehicle.updated', 'customer', $request->user()->id, 'Vehicle', $vehicle->id, 'success', $request->validated(), $request);

        return (new VehicleResource($vehicle))->response();
    }

    public function destroy(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->authorize('delete', $vehicle);

        $vehicle->delete();

        AuditLogger::log('vehicle.deleted', 'customer', $request->user()->id, 'Vehicle', $vehicle->id, 'success', [], $request);

        return response()->json(['message' => 'تم حذف المركبة.']);
    }
}
