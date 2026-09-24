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
        $vehicle = $request->user()->vehicles()->create($request->validated())->fresh();

        AuditLogger::log('vehicle.created', 'customer', $request->user()->id, 'Vehicle', $vehicle->id, 'success', [], $request);

        return (new VehicleResource($vehicle))->response()->setStatusCode(201);
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
