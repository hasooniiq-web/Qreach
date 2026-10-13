<?php

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;
use Illuminate\Notifications\Notification;

/** Sent to the inviter when a friend they invited makes a first paid activation. */
class ReferralRewardNotification extends Notification
{
    public function __construct(private readonly int $days) {}

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    /** @return array{title: string, body: string} */
    public function text(): array
    {
        return [
            'title' => 'حصلت على '.$this->days.' يومًا مجانية من بلس',
            'body' => 'اشترك صديق دعوته، فأضفنا الأيام إلى باقتك. شكرًا لنشرك Qreach.',
        ];
    }

    /** @return array{title: string, body: string, data: array<string, string>} */
    public function toFcm(object $notifiable): array
    {
        return $this->text() + ['data' => ['type' => 'subscription', 'kind' => 'referral_reward']];
    }

    public function toDatabase(object $notifiable): array
    {
        $text = $this->text();

        return [
            'type' => 'subscription',
            'action_type' => 'referral_reward',
            'title' => $text['title'],
            'message' => $text['body'],
            'occurred_at' => now()->toIso8601String(),
        ];
    }
}
