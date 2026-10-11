<?php

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;
use Illuminate\Notifications\Notification;

/**
 * A subscription reminder: a month, ten days and a day before the plan ends,
 * when it ended (the grace week starts) and when it returned to Basic.
 * Delivered in the app's notification list and as a push.
 */
class SubscriptionNoticeNotification extends Notification
{
    /**
     * @param  string  $kind  before_30 | before_10 | before_1 | expired | downgraded
     */
    public function __construct(
        private readonly string $kind,
        private readonly int $daysLeft,
        private readonly string $planName,
        private readonly string $endsOn,
        private readonly bool $renewalCanceled = false,
        private readonly int $graceDays = 7,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    /** @return array{title: string, body: string} */
    public function text(): array
    {
        $plan = $this->planName;
        $until = $this->endsOn;

        return match ($this->kind) {
            'expired' => [
                'title' => "انتهى اشتراك {$plan}",
                'body' => "لديك {$this->graceDays} أيام إضافية تبقى فيها المزايا تعمل. جدّد الآن وستبقى بياناتك كما هي.",
            ],
            'downgraded' => [
                'title' => 'عادت باقتك إلى الأساسية',
                'body' => "انتهت فترة السماح. بياناتك محفوظة ولم تُحذف، وستعود كما كانت فور تجديد {$plan}.",
            ],
            default => $this->renewalCanceled
                ? [
                    'title' => $this->daysLeft <= 1 ? "اشتراك {$plan} ينتهي غدًا" : "اشتراك {$plan} ينتهي بعد {$this->daysLeft} يومًا",
                    'body' => "أوقفتَ التجديد، فسينتهي الاشتراك في {$until}. يمكنك إعادة تفعيله من صفحة الاشتراك.",
                ]
                : [
                    'title' => $this->daysLeft <= 1 ? "اشتراك {$plan} ينتهي غدًا" : "اشتراك {$plan} ينتهي بعد {$this->daysLeft} يومًا",
                    'body' => "ينتهي اشتراكك في {$until}. جدّده للاستمرار في استخدام المزايا دون انقطاع.",
                ],
        };
    }

    /** @return array{title: string, body: string, data: array<string, string>} */
    public function toFcm(object $notifiable): array
    {
        return $this->text() + ['data' => ['type' => 'subscription', 'kind' => $this->kind]];
    }

    public function toDatabase(object $notifiable): array
    {
        $text = $this->text();

        return [
            'type' => 'subscription',
            'action_type' => $this->kind,
            'title' => $text['title'],
            'message' => $text['body'],
            'occurred_at' => now()->toIso8601String(),
        ];
    }
}
