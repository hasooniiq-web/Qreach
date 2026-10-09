<?php

namespace App\Services;

use App\Models\Device;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Single active session per customer account.
 *
 * Signing in revokes every earlier token of the account (the enforcement — a
 * revoked token no longer authenticates, so the old device gets a 401 on its
 * next request). To let that device explain itself ("you were signed out because
 * the account was opened on another phone" instead of a generic expiry), the
 * revoked tokens' hashes are remembered for a while and a later 401 for one of
 * them is reported as SESSION_REPLACED. That memory is cosmetic only: forgetting
 * it never lets a revoked token back in.
 */
class SessionRegistry
{
    private const MEMORY_DAYS = 30;

    /** Revokes every active token and device of the account. */
    public static function revokeAll(User $user): void
    {
        foreach ($user->tokens()->get(['id', 'token']) as $token) {
            Cache::put(self::key($token->token), true, now()->addDays(self::MEMORY_DAYS));
        }

        Device::where('user_id', $user->id)->where('is_active', true)
            ->update(['is_active' => false, 'revoked_at' => now()]);
        $user->tokens()->delete();
    }

    /** Was the bearer token on this request revoked because the account signed in elsewhere? */
    public static function wasReplaced(Request $request): bool
    {
        $bearer = $request->bearerToken();
        if (! $bearer) {
            return false;
        }

        // Sanctum tokens look like "<id>|<plain>"; the DB stores sha256(<plain>).
        $plain = str_contains($bearer, '|') ? explode('|', $bearer, 2)[1] : $bearer;

        return Cache::has(self::key(hash('sha256', $plain)));
    }

    private static function key(string $hash): string
    {
        return 'session-replaced:'.$hash;
    }
}
