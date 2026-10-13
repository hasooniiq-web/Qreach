<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Language of the public site pages (landing, reseller look-up): Arabic, English or
 * Kurdish (Sorani). Taken from ?lang=, else the remembered choice, else the browser's
 * Accept-Language, else Arabic. Only the three known codes are ever accepted.
 */
class SetPublicLocale
{
    public const LANGUAGES = ['ar', 'en', 'ckb'];

    public const COOKIE = 'qreach_lang';

    public function handle(Request $request, Closure $next): Response
    {
        $requested = $request->query('lang');
        $fromQuery = is_string($requested) && in_array($requested, self::LANGUAGES, true) ? $requested : null;

        $remembered = $request->cookie(self::COOKIE);
        $fromCookie = is_string($remembered) && in_array($remembered, self::LANGUAGES, true) ? $remembered : null;

        $locale = $fromQuery
            ?? $fromCookie
            ?? ($request->getPreferredLanguage(self::LANGUAGES) ?: 'ar');

        app()->setLocale($locale);

        if ($fromQuery && $fromQuery !== $fromCookie) {
            Cookie::queue(self::COOKIE, $fromQuery, 60 * 24 * 365);
        }

        return $next($request);
    }
}
