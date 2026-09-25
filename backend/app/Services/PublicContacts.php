<?php

namespace App\Services;

use App\Models\User;

/**
 * What a visitor may see about the owner on the public scan page: only
 * contact methods the owner marked public, and only of types their current
 * plan allows (Pro). Re-evaluated on every render, so a lapsed subscription
 * hides the details immediately without deleting what the owner entered.
 */
class PublicContacts
{
    public function __construct(private readonly PlanEntitlements $plans) {}

    /** @return list<array{type: string, label: string, value: string, href: ?string}> */
    public function visibleTo(User $owner): array
    {
        $allowed = $this->plans->planFor($owner)->publicContactTypes();

        if ($allowed === []) {
            return [];
        }

        return $owner->contactMethods()
            ->where('privacy_state', 'public')
            ->whereIn('type', $allowed)
            ->orderBy('id')
            ->get()
            ->map(fn ($m) => [
                'type' => $m->type,
                'label' => $this->label($m->type, $m->label),
                'value' => $m->value,
                'href' => $this->href($m->type, $m->value),
            ])
            ->all();
    }

    private function label(string $type, ?string $custom): string
    {
        return match ($type) {
            'phone' => 'اتصال هاتفي',
            'instagram' => 'إنستغرام',
            'facebook' => 'فيسبوك',
            'website' => 'الموقع',
            'email' => 'البريد الإلكتروني',
            default => $custom ?: 'معلومات إضافية',
        };
    }

    private function href(string $type, string $value): ?string
    {
        return match ($type) {
            'phone' => 'tel:'.preg_replace('/[^\d+]/', '', $value),
            'instagram' => 'https://instagram.com/'.ltrim($value, '@'),
            'email' => 'mailto:'.$value,
            'facebook', 'website' => str_starts_with($value, 'https://') ? $value : null,
            default => null,
        };
    }
}
