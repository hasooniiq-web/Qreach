<?php

namespace App\Http\Resources;

use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = $request->getPreferredLanguage(SubscriptionPlan::LANGUAGES) ?? 'ar';

        return [
            'slug' => $this->slug,
            // Written per language in the dashboard; the app's Accept-Language picks one.
            'name' => $this->nameIn($lang),
            'description' => $this->descriptionIn($lang),
            'price' => $this->price_cents,
            'currency' => $this->currency,
            'is_free' => $this->isFree(),
            'billing_period_days' => $this->billing_period_days,
            'features' => [
                'messaging' => $this->allowsMessaging(),
                'public_contacts' => $this->publicContactTypes(),
                'extra_contact_methods' => $this->allowsExtraMethods(),
                'emergency_contact' => $this->allowsEmergencyContact(),
            ],
        ];
    }
}
