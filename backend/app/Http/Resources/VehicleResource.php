<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'nickname' => $this->nickname,
            'plate_number' => $this->plate_number,
            'plate_country' => $this->plate_country,
            'plate_type' => $this->plate_type ?? 'private',
            'vin' => $this->vin,
            'plate_letter' => $this->plate_letter,
            'plate_governorate' => $this->plate_governorate,
            'plate_style' => $this->plate_style ?? 'other',
            // Held back by the plan: the free plan keeps one vehicle active, Plus three.
            'locked_by_plan' => $this->lockedByPlan($request),
            'make' => $this->make,
            'model' => $this->model,
            'year' => $this->year,
            'color' => $this->color,
            'vehicle_type' => $this->vehicle_type,
            'description' => $this->description,
            'status' => $this->status,
            'privacy_settings' => $this->privacy_settings,
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            // The code the owner removed last: it can be linked again in one tap.
            'previous_tag' => $this->whenLoaded('tags', function () {
                $assigned = $this->tags->contains(fn ($t) => $t->status === 'assigned');
                $removed = $this->tags
                    ->filter(fn ($t) => $t->status === 'revoked' && $t->revocation_reason === 'removed by owner')
                    ->sortByDesc('revoked_at')
                    ->first();

                return ! $assigned && $removed
                    ? [
                        'serial_number' => $removed->serial_number,
                        'removed_at' => $removed->revoked_at?->toIso8601String(),
                    ]
                    : null;
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /** Whether the owner's plan no longer covers this vehicle (computed once per request per owner). */
    private function lockedByPlan(Request $request): bool
    {
        $key = 'locked_vehicle_ids:'.$this->user_id;

        if (! $request->attributes->has($key)) {
            $owner = \App\Models\User::find($this->user_id);
            $request->attributes->set($key, $owner ? app(\App\Services\PlanEntitlements::class)->lockedVehicleIds($owner) : []);
        }

        return in_array($this->id, $request->attributes->get($key), true);
    }
}