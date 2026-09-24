<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default-deny, server-side permission check (section 20/26). Usage:
 * ->middleware('admin.permission:qr.generate_batch')
 */
class EnsureAdminHasPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $admin = auth('admin')->user();

        if (! $admin || ! $admin->hasPermission($permission)) {
            abort(403, 'ليست لديك صلاحية للوصول إلى هذا المورد.');
        }

        return $next($request);
    }
}
