<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards every dashboard route. Distinct from Laravel's default `auth`
 * middleware because admins use their own guard/session (section 20) and,
 * once TOTP is enabled, must also have completed 2FA for the current
 * session (checked separately by EnsureAdminTotpVerified).
 */
class EnsureAdminAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth('admin')->check()) {
            return redirect()->route('admin.login');
        }

        $admin = auth('admin')->user();

        if ($admin->status !== 'active') {
            auth('admin')->logout();

            return redirect()->route('admin.login')->withErrors(['email' => 'هذا الحساب موقوف. تواصل مع مسؤول النظام.']);
        }

        return $next($request);
    }
}
