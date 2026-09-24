<?php

namespace App\Services\Sms;

use App\Contracts\SmsGatewayContract;
use Illuminate\Support\Facades\Log;

/**
 * Development-only SMS gateway. The spec names no SMS provider, so real
 * delivery is deliberately deferred (see docs/ARCHITECTURE_DECISIONS.md)
 * rather than guessing one — swap the `SmsGatewayContract` binding in
 * AppServiceProvider for a real provider (e.g. Unifonic, Twilio) before
 * production. This implementation must NEVER run in production: it logs
 * the OTP in plaintext, which section 23 explicitly forbids for real use.
 */
class LogSmsGateway implements SmsGatewayContract
{
    public function send(string $phone, string $message): bool
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('LogSmsGateway must not be used in production. Configure a real SMS gateway.');
        }

        Log::channel('single')->info('sms.dev_send', ['phone' => $phone, 'message' => $message]);

        return true;
    }
}
