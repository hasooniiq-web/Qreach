<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * A flood ceiling per network address, applied before anything else runs (before the
 * session, before sign-in), so a burst of junk requests costs one cache lookup instead
 * of a session read and a database query each.
 *
 * It is a ceiling, not the everyday limit: the sign-in, OTP, scan and export routes have
 * their own much stricter limits, and a signed-in customer has the per-account limit
 * (see AppServiceProvider). It only has to be out of reach of a person and in reach of a script.
 */
class ThrottleByAddress
{
    public function handle(Request $request, Closure $next): Response
    {
        $api = $request->is('api/*');
        $max = (int) config($api ? 'security.api_per_minute' : 'security.web_per_minute', 600);
        $key = ($api ? 'ceiling-api:' : 'ceiling-web:').$request->ip();

        if (RateLimiter::tooManyAttempts($key, $max)) {
            $retry = max(1, RateLimiter::availableIn($key));

            if ($api || $request->expectsJson()) {
                return response()->json([
                    'error' => ['code' => 'RATE_LIMITED', 'message' => 'عدد الطلبات كبير. حاول بعد قليل.', 'retry_after' => $retry],
                ], 429)->header('Retry-After', (string) $retry);
            }

            abort(429, 'عدد الطلبات كبير. حاول بعد قليل.');
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }
}
