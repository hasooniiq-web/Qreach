<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;

/**
 * Central write path for audit_logs (section 21). Every sensitive mutation
 * in the system must go through this rather than writing to the table
 * directly, so the "never log secrets" rule is enforced in one place.
 */
class AuditLogger
{
    private const FORBIDDEN_KEYS = ['password', 'otp', 'code', 'token', 'secret', 'totp_secret', 'recovery_codes'];

    public static function log(
        string $action,
        string $actorType,
        ?int $actorId,
        ?string $resourceType = null,
        ?int $resourceId = null,
        string $result = 'success',
        array $metadata = [],
        ?Request $request = null,
    ): AuditLog {
        $request ??= request();

        return AuditLog::create([
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'request_ip' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'result' => $result,
            'metadata' => self::sanitize($metadata),
        ]);
    }

    private static function sanitize(array $metadata): array
    {
        foreach ($metadata as $key => $value) {
            foreach (self::FORBIDDEN_KEYS as $forbidden) {
                if (str_contains(strtolower((string) $key), $forbidden)) {
                    $metadata[$key] = '[REDACTED]';
                    continue 2;
                }
            }

            if (is_array($value)) {
                $metadata[$key] = self::sanitize($value);
            }
        }

        return $metadata;
    }
}
