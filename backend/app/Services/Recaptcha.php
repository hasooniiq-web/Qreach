<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Server-side check of a Google reCAPTCHA v2 token. The widget on the page only
 * produces a token; nothing is trusted until Google confirms it here.
 */
class Recaptcha
{
    public const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    public function enabled(): bool
    {
        return filled(config('services.recaptcha.site_key')) && filled(config('services.recaptcha.secret_key'));
    }

    public function siteKey(): ?string
    {
        return config('services.recaptcha.site_key');
    }

    public function verify(?string $token, ?string $ip = null): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        if (blank($token)) {
            return false;
        }

        try {
            $response = Http::asForm()->timeout(8)->post(self::VERIFY_URL, array_filter([
                'secret' => config('services.recaptcha.secret_key'),
                'response' => $token,
                'remoteip' => $ip,
            ]));
        } catch (\Throwable $e) {
            // Fail closed: if Google cannot be reached, the login is refused rather than unprotected.
            Log::warning('reCAPTCHA verification unreachable: '.$e->getMessage());

            return false;
        }

        return $response->successful() && $response->json('success') === true;
    }
}