<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Section 23: admin MFA. Every administrator must have TOTP (a Google
 * Authenticator-style app) enabled — one without it is sent to the enrollment
 * screen — and the session must carry the `admin_totp_verified_at` flag set
 * at sign-in before any dashboard route is reachable. The flag is cleared on
 * every new login, so the code is asked each time.
 */
class EnsureAdminTotpVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = auth('admin')->user();

        if ($admin && config('admin.require_totp') && ! $admin->totp_enabled) {
            return redirect()->route('admin.totp.setup');
        }

        if ($admin && $admin->totp_enabled && ! $request->session()->get('admin_totp_verified_at')) {
            return redirect()->route('admin.totp.challenge');
        }

        return $next($request);
    }
}
