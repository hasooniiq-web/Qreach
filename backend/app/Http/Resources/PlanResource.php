<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name_ar,
            'description' => $this->description_ar,
            'price' => $this->price_cents,
            'currency' => $this->currency,
            'is_free' => $this->isFree(),
            'billing_period_days' => $this->billing_period_days,
            'features' => [
                'messaging' => $this->allowsMessaging(),
                'public_contacts' => $this->publicContactTypes(),
            ],
        ];
    }
}
