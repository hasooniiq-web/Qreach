<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    public const BASIC = 'basic';

    public const PLUS = 'plus';

    /**
     * Contact-method types that exist. Basic may show only its phone; Plus may add
     * and show all of them (what a plan allows lives in its `features` JSON).
     */
    public const PUBLIC_CONTACT_TYPES = ['phone', 'whatsapp', 'telegram', 'instagram', 'facebook', 'snapchat', 'tiktok', 'twitter', 'linkedin', 'youtube', 'website', 'email', 'other'];

    protected $fillable = [
        'slug', 'name_ar', 'name_en', 'name_ckb', 'description_ar', 'description_en', 'description_ckb', 'price_cents', 'currency', 'billing_period_days',
        'max_vehicles', 'max_tags', 'is_active', 'features', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'features' => 'array'];
    }

    /** App languages a plan's name and description are written in (Arabic is the required base). */
    public const LANGUAGES = ['ar', 'en', 'ckb'];

    /** The plan's name in a language; falls back to Arabic when that language was left empty. */
    public function nameIn(?string $language): string
    {
        $value = match ($language) {
            'en' => $this->name_en,
            'ckb' => $this->name_ckb,
            default => $this->name_ar,
        };

        return filled($value) ? $value : $this->name_ar;
    }

    public function descriptionIn(?string $language): ?string
    {
        $value = match ($language) {
            'en' => $this->description_en,
            'ckb' => $this->description_ckb,
            default => $this->description_ar,
        };

        return filled($value) ? $value : $this->description_ar;
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    public function allowsMessaging(): bool
    {
        return (bool) ($this->features['messaging'] ?? true);
    }

    /** @return list<string> */
    public function publicContactTypes(): array
    {
        return array_values(array_intersect(self::PUBLIC_CONTACT_TYPES, $this->features['public_contacts'] ?? []));
    }

    public function allowsPublicContact(string $type): bool
    {
        return in_array($type, $this->publicContactTypes(), true);
    }

    /** Plus: may create contact methods beyond the phone number. */
    public function allowsExtraMethods(): bool
    {
        return (bool) ($this->features['extra_contact_methods'] ?? false);
    }

    public function allowsEmergencyContact(): bool
    {
        return (bool) ($this->features['emergency_contact'] ?? true);
    }

    public function isFree(): bool
    {
        return $this->price_cents === 0;
    }
}
