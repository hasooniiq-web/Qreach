<?php

namespace App\Services;

use App\Models\SecurityEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Security-event subsystem (section 22). Critical events also go to the
 * dedicated `security` log channel so they reach external monitoring
 * independently of the database (section 40).
 */
class SecurityEventLogger
{
    public const AUTH_FAILURE = 'AUTH_FAILURE';

    public const AUTH_SUCCESS = 'AUTH_SUCCESS';

    public const OTP_ABUSE = 'OTP_ABUSE';

    public const ACCOUNT_LOCKED = 'ACCOUNT_LOCKED';

    public const SUSPICIOUS_SCAN = 'SUSPICIOUS_SCAN';

    public const QR_GUESSING = 'QR_GUESSING';

    public const RATE_LIMIT_TRIGGERED = 'RATE_LIMIT_TRIGGERED';

    public const PRIVILEGE_CHANGE = 'PRIVILEGE_CHANGE';

    public const MASS_EXPORT = 'MASS_EXPORT';

    public const ADMIN_SECURITY_EVENT = 'ADMIN_SECURITY_EVENT';

    public const SESSION_ANOMALY = 'SESSION_ANOMALY';

    public const TOKEN_ANOMALY = 'TOKEN_ANOMALY';

    public const UNUSUAL_REQUEST_PATTERN = 'UNUSUAL_REQUEST_PATTERN';

    public const EMERGENCY_TRIGGERED = 'EMERGENCY_TRIGGERED';

    public const PAYMENT_ANOMALY = 'PAYMENT_ANOMALY';

    public static function log(
        string $eventType,
        string $severity = 'info',
        string $actorType = 'unknown',
        ?int $actorId = null,
        array $metadata = [],
        ?Request $request = null,
    ): SecurityEvent {
        $request ??= request();

        $event = SecurityEvent::create([
            'event_type' => $eventType,
            'severity' => $severity,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'request_ip' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'metadata' => $metadata,
        ]);

        if ($severity === 'critical' || $severity === 'warning') {
            Log::channel('security')->{$severity === 'critical' ? 'critical' : 'warning'}(
                $eventType,
                ['id' => $event->id, 'actor_type' => $actorType, 'actor_id' => $actorId, 'ip' => $request?->ip()]
            );
        }

        return $event;
    }
}
