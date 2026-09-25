<?php

namespace App\Notifications;

use App\Models\Scan;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Notifications\Notification;

/**
 * Delivered to the vehicle owner for every public action (section 13/15).
 * Queued so the visitor-facing request never waits on notification
 * delivery (section 15). Currently database-only — push delivery needs
 * real FCM/APNs credentials before production (see docs decision).
 */
class TagActionNotification extends Notification
{
    private const TITLES = [
        'vehicle_block' => 'تنبيه: مركبتك تعيق المرور',
        'lights_on' => 'تنبيه: أضواء مركبتك مضاءة',
        'important_message' => 'رسالة بخصوص مركبتك',
        'emergency' => 'تنبيه طارئ بخصوص مركبتك',
    ];

    public function __construct(private readonly Scan $scan) {}

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    /** @return array{title: string, body: string, data: array<string, string>} */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => self::TITLES[$this->scan->action_type] ?? 'تنبيه بخصوص مركبتك',
            'body' => $this->scan->payload['message'] ?? 'افتح التطبيق للتفاصيل',
            'data' => ['type' => 'tag_action', 'action_type' => $this->scan->action_type],
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'tag_action',
            'action_type' => $this->scan->action_type,
            'title' => self::TITLES[$this->scan->action_type] ?? 'تنبيه بخصوص مركبتك',
            'vehicle_id' => $this->scan->vehicle_id,
            'message' => $this->scan->payload['message'] ?? null,
            'scan_id' => $this->scan->id,
            'occurred_at' => $this->scan->created_at->toIso8601String(),
        ];
    }
}
