<?php

namespace App\Services;

use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends Firebase Cloud Messaging pushes (HTTP v1) to a customer's devices.
 *
 * Needs a Firebase *service-account* key (Firebase console -> Project settings
 * -> Service accounts -> Generate new private key), saved at FIREBASE_CREDENTIALS
 * (default storage/app/firebase-service-account.json). That is different from
 * the app's google-services.json, which only identifies the app. Without the
 * key nothing is sent and the in-app feed keeps working on its own.
 *
 * Delivery is best-effort: a push failure is logged and never breaks the
 * request that caused it.
 */
class FcmSender
{
    public function credentialsPath(): string
    {
        return config('services.firebase.credentials') ?: storage_path('app/firebase-service-account.json');
    }

    /** @return array<string, mixed>|null */
    private function credentials(): ?array
    {
        $path = $this->credentialsPath();
        if (! is_file($path)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) && ($data['type'] ?? null) === 'service_account' && isset($data['private_key'], $data['client_email'], $data['project_id'])
            ? $data : null;
    }

    public function configured(): bool
    {
        return $this->credentials() !== null;
    }

    /** @param array<string, string> $data */
    public function sendToUser(User $user, string $title, string $body, array $data = []): int
    {
        $credentials = $this->credentials();
        if (! $credentials) {
            return 0;
        }

        $devices = Device::where('user_id', $user->id)->where('is_active', true)
            ->where('push_provider', 'fcm')->whereNotNull('push_token')->get();
        if ($devices->isEmpty()) {
            return 0;
        }

        try {
            $token = $this->accessToken($credentials);
        } catch (\Throwable $e) {
            Log::warning('FCM auth failed: '.$e->getMessage());

            return 0;
        }

        $sent = 0;
        foreach ($devices as $device) {
            $response = Http::withToken($token)->timeout(8)->post(
                "https://fcm.googleapis.com/v1/projects/{$credentials['project_id']}/messages:send",
                ['message' => [
                    'token' => $device->push_token,
                    'notification' => ['title' => $title, 'body' => mb_substr($body, 0, 180)],
                    // FCM wants a JSON object; an empty PHP array would encode as a list and be rejected.
                    'data' => (object) array_map('strval', $data),
                    // The phone's own default notification sound (no custom sound file).
                    'android' => ['priority' => 'HIGH', 'notification' => ['sound' => 'default', 'default_sound' => true, 'default_vibrate_timings' => true, 'notification_priority' => 'PRIORITY_HIGH']],
                    // iOS (via APNs): alert push at high priority with the default sound. Without
                    // this FCM picks defaults that can deliver silently or delayed.
                    'apns' => ['headers' => ['apns-priority' => '10', 'apns-push-type' => 'alert'], 'payload' => ['aps' => ['sound' => 'default']]],
                ]]
            );

            if ($response->successful()) {
                $sent++;
            } elseif (in_array($response->status(), [400, 404], true) && str_contains((string) $response->body(), 'UNREGISTERED')) {
                // The app was uninstalled or the token rotated: stop sending to it.
                $device->update(['is_active' => false]);
            } else {
                Log::warning('FCM send failed', ['status' => $response->status(), 'body' => $response->body()]);
            }
        }

        return $sent;
    }

    /** OAuth access token from the service account (JWT bearer flow), cached until shortly before expiry. */
    private function accessToken(array $credentials): string
    {
        return Cache::remember('fcm.access_token.'.md5($credentials['client_email']), 3300, function () use ($credentials) {
            $now = time();
            $b64 = fn (string $s) => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
            $unsigned = $b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])).'.'.$b64(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));

            if (! openssl_sign($unsigned, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new \RuntimeException('Could not sign the service-account JWT.');
            }

            $response = Http::asForm()->timeout(8)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $unsigned.'.'.$b64($signature),
            ]);

            return $response->throw()->json('access_token');
        });
    }
}
