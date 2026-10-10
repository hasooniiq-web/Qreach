<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security headers for every response (section 37 / OWASP A05).
 * CSP is intentionally conservative for the server-rendered Arabic RTL
 * dashboard (Blade + Tailwind, no third-party script origins).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        $response->headers->set('X-XSS-Protection', '0');

        if (! $request->is('t/*')) {
            // The public tag page intentionally allows itself to be opened from
            // any context a scanned QR/NFC link is followed from; everything
            // else is locked down.
            $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        }

        if ($request->secure() || app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if (! $response->headers->has('Content-Security-Policy')) {
            // Google's widget needs its own script, frame and image hosts — granted only on the admin login page.
            $captcha = $request->is('admin/login') && app(\App\Services\Recaptcha::class)->enabled();

            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self'; img-src 'self' data:".($captcha ? ' https://www.gstatic.com https://www.google.com' : '')."; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com".($captcha ? ' https://www.gstatic.com' : '').'; '.
                "font-src 'self' https://fonts.gstatic.com; script-src 'self'".($captcha ? ' https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/' : '').'; '.
                ($captcha ? 'frame-src https://www.google.com/recaptcha/ https://recaptcha.google.com/recaptcha/; ' : '').
                "frame-ancestors 'none'; base-uri 'self'; form-action 'self'"
            );
        }

        return $response;
    }
}
