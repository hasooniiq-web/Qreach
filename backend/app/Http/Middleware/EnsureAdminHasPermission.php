<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default-deny, server-side permission check (section 20/26). Usage:
 * ->middleware('admin.permission:qr.generate_batch')
 * Accepts several comma-separated keys as "any of" — e.g.
 * ->middleware('admin.permission:audit.view,audit_logs.view') — for a
 * permission being renamed/split without breaking admins who already hold
 * the older key.
 */
class EnsureAdminHasPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $admin = auth('admin')->user();
        $keys = array_filter(array_map('trim', explode(',', $permission)));

        if (! $admin || ! collect($keys)->contains(fn ($key) => $admin->hasPermission($key))) {
            abort(403, 'ليست لديك صلاحية للوصول إلى هذا المورد.');
        }

        return $next($request);
    }
}
