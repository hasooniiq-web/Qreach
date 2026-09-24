<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Section 23: admin MFA. If the admin has TOTP enabled, the session must
 * carry a `totp_verified_at` flag set during login before any dashboard
 * route (other than the TOTP challenge itself) is reachable.
 */
class EnsureAdminTotpVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = auth('admin')->user();

        if ($admin && $admin->totp_enabled && ! $request->session()->get('admin_totp_verified_at')) {
            return redirect()->route('admin.totp.challenge');
        }

        return $next($request);
    }
}
