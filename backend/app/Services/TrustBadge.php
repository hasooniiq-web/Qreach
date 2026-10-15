<?php

namespace App\Services;

use App\Models\ConversationReport;
use App\Models\User;

/**
 * What an owner is told about someone who messaged them from the app when that person chose
 * not to show their name. Never an identity — only how much the account can be relied on:
 *
 *  - "trusted":  an account older than 30 days with a vehicle that has a linked code, and no
 *                complaint against it that the team has reviewed.
 *  - "verified": every other registered account (the phone number is confirmed by a code).
 */
class TrustBadge
{
    public const TRUSTED = 'trusted';

    public const VERIFIED = 'verified';

    public function for(User $user): string
    {
        $old = $user->created_at !== null && $user->created_at->lte(now()->subDays(30));

        $hasLinkedVehicle = $user->vehicles()
            ->whereHas('tags', fn ($q) => $q->where('status', 'assigned'))
            ->exists();

        $complaints = ConversationReport::where('reported_user_id', $user->id)->where('status', 'reviewed')->exists();

        return $old && $hasLinkedVehicle && ! $complaints ? self::TRUSTED : self::VERIFIED;
    }
}
