<?php

namespace App\Services;

use App\Models\User;

/**
 * What a visitor may see about the owner on the public scan page. The default
 * is nothing at all: not the name, not the phone, not any contact method. Each
 * item appears only if (a) the owner switched it on and (b) the owner's current
 * plan allows showing it. Re-evaluated on every render, so a lapsed Plus plan
 * hides the extra platforms immediately without deleting what the owner typed.
 */
class PublicContacts
{
    public function __construct(private readonly PlanEntitlements $plans) {}

    /** The owner's name, only if they chose to show it. */
    public function nameFor(User $owner): ?string
    {
        return $owner->show_name_publicly && filled($owner->name) ? $owner->name : null;
    }

    /** The scan-page URL for the owner's avatar, only if they chose to show it. */
    public function avatarUrlFor(User $owner, string $token): ?string
    {
        return $owner->show_avatar_publicly && $owner->avatar_path ? route('public.scan.avatar', $token) : null;
    }

    /** @return array{name: string, relationship: ?string, phone: string}|null */
    public function emergencyFor(User $owner): ?array
    {
        if (! $this->plans->planFor($owner)->allowsEmergencyContact()) {
            return null;
        }

        $contact = $owner->emergencyContacts()->where('is_public', true)->where('status', 'active')->orderBy('priority')->first();

        return $contact
            ? ['name' => $contact->name, 'relationship' => $contact->relationship, 'phone' => $contact->phone]
            : null;
    }

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
                'label' => ContactPlatforms::label($m->type, $m->label),
                'value' => ContactPlatforms::display($m->type, $m->value),
                'href' => ContactPlatforms::href($m->type, $m->value),
            ])
            ->all();
    }
}
