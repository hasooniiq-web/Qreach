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
            'make' => $this->make,
            'model' => $this->model,
            'year' => $this->year,
            'color' => $this->color,
            'vehicle_type' => $this->vehicle_type,
            'description' => $this->description,
            'status' => $this->status,
            'privacy_settings' => $this->privacy_settings,
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
